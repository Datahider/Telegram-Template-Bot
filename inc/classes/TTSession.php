<?php

/**
 * Description of TTSession
 *
 * @author drweb
 */
class TTSession {
    protected $user;
    protected $chat;
    protected $data;
    
    protected $user_id;
    protected $chat_id;


    public function __construct($user, $chat) {
        $this->user = $user;
        $this->chat = $chat;
        
        $this->loadSession();
    }
    
    protected function loadSession() {
        $this->chat_id = $this->chat->getId();
        $this->user_id = -1;
        
        if ($this->user) {
            $this->user_id = $this->user->getId();
        }
        
        $this->data = [];
        
        foreach ($this->sqlGetSessionData() as $param) {
            $this->data[$param['param_name']] = $param['param_value'];
        }
    }
    
    protected function sqlGetSessionData() {
        global $pdo, $config;
        
        $sth = $pdo->prepare("SELECT param_name, param_value "
                . "FROM {$config->db_prefix}sessiondata"
                . "WHERE user_id = :user_id AND chat_id = :chat_id"
        ); 
                
        $sth->execute([
            'chat_id' => $this->chat_id,
            'user_id' => $this->user_id
        ]);
        
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function get($param_name, $default=null) {
        global $config;
        
        switch ($param_name) {
            case 'user':
                return $this->user;
            case 'chat':
                return $this->chat;
            case 'user_id':
                return $this->user_id;
            case 'chat_id':
                return $this->chat_id;
            default:
                if (isset($config->session_static[$param_name])) {
                    return $config->session_static[$param_name];
                }
                if (isset($this->data[$param_name])) {
                    return $this->data[$param_name];
                }
                return $default;
        }
    }
    
    public function set($param_name, $param_value) {
        switch ($param_name) {
            case 'user':
            case 'chat':
            case 'user_id':
            case 'chat_id':
                return;
        }
        
        $this->data[$param_name] = $param_value;
        $this->sqlSetSessionParam($param_name, $param_value);
    }
    
    public function sqlSetSessionParam($param_name, $param_value) {
        global $pdo, $config;
        
        $sth = $pdo->prepare(
                "INSERT INTO {$config->db_prefix}sessiondata (chat_id, user_id, param_name, param_value)"
                . "VALUES (:chat_id, :user_id, :param_name, :param_value) "
                . "ON DUPLICATE KEY UPDATE param_name = :param_name, param_value = :param_value"
        );
                
        $sth->execute([
            'chat_id' => $this->chat_id,
            'user_id' => $this->user_id,
            'param_name' => $param_name,
            'param_value' => $param_value
        ]);
    }
}
