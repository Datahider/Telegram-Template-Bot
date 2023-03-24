<?php

/**
 * Description of TTSession
 *
 * @author drweb
 */
class TTSession {
    const MAGIC = 'KpLd4d8oku_fZiPWtmAaad9uMPdZNrxY3Js4GdEdpN';
    
    protected $user;
    protected $chat;
    protected $data;

    public function __construct($user, $chat) {
        $this->user = $user;
        $this->chat = $chat;
        
        $this->loadSession();
    }
    
    protected function loadSession() {
        $this->data['chat_id'] = $this->chat->getId();
        $this->data['user_id'] = -1;
        
        if ($this->user) {
            $this->data['user_id'] = $this->user->getId();
        }
        
        $this->data['user'] = $this->user;
        $this->data['chat'] = $this->chat;
        
        foreach ($this->sqlGetSessionData() as $param) {
            $value = unserialize($param['param_value']);
            if ($value === false && serialize($value) != $param['param_value']) {
                $value = $param['param_value'];
            }
            $this->data[$param['param_name']] = $value;
        }
        
        if ( ($this->data['user_id'] != -1) ) {
            if (!isset($this->data['call_name'])) {
                $this->set('call_name', $this->user->getFirstName(), true);
            }
            if (!isset($this->data['alias'])) {
                $alias = $this->user->getLastName() 
                        ? $this->user->getFirstName(). ' '. $this->user->getLastName()
                        : $this->user->getFirstName();
                $this->set('alias', $alias, true);
            }
        }
    }
    
    protected function sqlGetSessionData() {
        global $pdo, $config;
        
        $sth = $pdo->prepare("SELECT param_name, param_value "
                . "FROM {$config->db_prefix}sessiondata "
                . "WHERE user_id = :user_id AND chat_id = :chat_id"
        ); 
                
        $sth->execute([
            'chat_id' => $this->data['chat_id'],
            'user_id' => $this->data['user_id']
        ]);
        
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function get($param_name, $default=null) {
        global $config;
        
        if (isset($config->session_static[$param_name])) {
            return $config->session_static[$param_name];
        }
        if (isset($this->data[$param_name])) {
            return $this->data[$param_name];
        }
        return $default;
    }
    
    public function getData() {
        return $this->data;
    }

    public function set($param_name, $param_value, $persistent=true) {
        switch ($param_name) {
            case 'user':
            case 'chat':
            case 'user_id':
            case 'chat_id':
                return;
        }
        
        if ($param_value !== null) {
            $this->data[$param_name] = $param_value;
            if ($persistent) {
                $this->sqlSetSessionParam($param_name, $param_value);
            }
        } else {
            unset($this->data[$param_name]);
            if ($persistent) {
                $this->sqlUnsetSessionParam($param_name);
            }
        }
    }
    
    public function setParams(array $params, $value=self::MAGIC) {
        if ($value == self::MAGIC) {
            foreach ($params as $key => $value) {
                $this->set($key, $value);
            } 
        } else {
            foreach ($params as $key) {
                $this->set($key, $value);
            }
        }
    }
    
    public function sqlSetSessionParam($param_name, $param_value) {
        global $pdo, $config;
        
        $sth = $pdo->prepare(
                "INSERT INTO {$config->db_prefix}sessiondata (chat_id, user_id, param_name, param_value)"
                . "VALUES (:chat_id, :user_id, :param_name, :param_value) "
                . "ON DUPLICATE KEY UPDATE param_name = :param_name, param_value = :param_value"
        );
                
        $sth->execute([
            'chat_id' => $this->data['chat_id'],
            'user_id' => $this->data['user_id'],
            'param_name' => $param_name,
            'param_value' => serialize($param_value)
        ]);
    }
    
    public function sqlUnsetSessionParam($param_name) {
        global $pdo, $config;
        
        $sth = $pdo->prepare(<<<SQL
            DELETE FROM
                {$config->db_prefix}sessiondata
            WHERE
                chat_id = :chat_id
                AND user_id = :user_id
                AND param_name = :param_name
            SQL    
        );

        $sth->execute([
            'chat_id' => $this->data['chat_id'],
            'user_id' => $this->data['user_id'],
            'param_name' => $param_name,
        ]);
    }
}
