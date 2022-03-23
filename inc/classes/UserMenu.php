<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

/**
 * Description of UserMenu
 *
 * @author drweb
 */
class UserMenu extends AbstractMenuMember {
    
    protected $name;
    protected $text;
    protected $parse_mode;

    public function __construct(string $name, string $representation, string $text, array $options, string $parse_mode=null) {
        $this->name = $name;
        $this->representation = $representation;
        $this->text = $text;
        $this->options = $options;
        $this->parse_mode = $parse_mode;
        $this->api = null;
    }
        
    public function show() {
        $this->checkApi();
        $this->api->session()->set(AbstractMenuMember::CURRENT_MENU_NAME, $this->name);
        
        $this->api->session()->set('object', $this, false);
        
        $keyboard = $this->prepareKeyboard();
        $message_id = $this->api->session()->get(self::CALLBACK_MESSAGE_ID, 0);
        
        if ($message_id) {
            $this->api->edit($message_id, $this->text, $this->parse_mode, $keyboard, false, []);
        } else {
            $this->api->answer($this->text, $this->parse_mode, $keyboard, false, []);
        }
    }
    
    public function handle($update) {
        try {
            $this->checkApi();
            if ($this->api->session()->get(AbstractMenuMember::CURRENT_MENU_NAME, false) == $this->name) {
                $this->handleSelf($update);
            } else {
                $this->handleOptions($update);
            }
        } catch (Exception $e) {
            if ($this->isNormalFlow($e)) {
                $this->answerCallback();
                if ($e->getMessage() == AbstractMenuMember::HANDLE_RESULT_PROGRESS 
                        && $this->api->session()->get(AbstractMenuMember::CURRENT_MENU_NAME, false) == $this->name) {
                    $this->show();
                } elseif ($e->getMessage() == AbstractMenuMember::HANDLE_RESULT_FINISHED) {
                    if ($e->getCode() > 0) {
                        throw new TTException($e->getMessage(), $e->getCode()-1);
                    } else {
                        $this->show();
                        throw new TTException(AbstractMenuMember::HANDLE_RESULT_PROGRESS);
                    }
                }
                throw $e;
            }
            $this->exceptionHandler($e);
        }
    }
    
    protected function handleSelf($update) {
        if ($message = $update->getMessage()) {
            $this->handleMessage($message);
        } elseif ($callback = $update->get('callback_query')) {
            $this->handleCallback($callback);
        } else {
            throw new TTException("Can't handle non message or callback update");
        }
    }
    
    protected function handleMessage($message) {
        foreach ($this->options as $option) {
            if (!is_a($option, 'AbstractMessageHandler')) { continue; }
            $option->handle($message);
        }
    }
    
    protected function setCallbackData($callback) {
        $message_id = $callback->getMessage()->get('message_id');
        $callback_id = $callback->getId();
        $this->api->session()->setParams([
            self::CALLBACK_MESSAGE_ID => $message_id,
            self::LAST_CALLBACK_ID => $callback_id
        ]);
    }
    
    protected function handleCallback($callback) {
        $data = $callback->get('data');
        $this->setCallbackData($callback);
        
        foreach ($this->options as $option) {
            if (($option->value() == $data) && is_a($option, 'UserMenu')) {
                $option->show();
                $this->answerCallback();
                throw new TTException(AbstractMenuMember::HANDLE_RESULT_PROGRESS);
            } elseif (($option->value() == $data) && is_a($option, 'SetValue')) {
                $option->set();
            } elseif (($option->value() == $data) && is_a($option, 'AbstractAction')) {
                $option->run();
            }
        }
    }

    protected function answerCallback() {
        $callback_id = $this->api->session()->get(AbstractMenuMember::LAST_CALLBACK_ID, 0);
        if ($callback_id) {
            $this->api->answerCallbackQuery(['callback_query_id' => $callback_id]);
            $this->api->session()->set(AbstractMenuMember::LAST_CALLBACK_ID, 0);
        }
    }

    protected function handleOptions($update) {
        foreach ($this->options as $option) {
            if (!is_a($option, 'UserMenu')) { continue; }
            $option->handle($update);
        }
    }
    
    protected function prepareKeyboard() {
        $rows = [];
        $buttons = [];
        foreach ($this->options as $option) {
            if (is_a($option, 'AbstractMessageHandler')) { continue; }
            if (!is_a($option, 'AbstractMenuMember')) {
                throw new TTException('$options must be an array of instances of AbstractMenuMember descendants');
            } elseif (is_a($option, 'LineSeparator')) {
                $rows[] = $buttons;
                $buttons = [];
            } else {
                $buttons[] = ['text' => $option->representation(), 'callback_data' => $option->value()];
            }
        }
        $rows[] = $buttons;
        return $rows;
    }
    
    public function value() {
        return $this->name;
    }
    
    protected function exceptionHandler($e) {
        global $config;
        if (!is_a($e, 'TTException')) {
            throw $e;
        }

        // use translations from $config
        $menu_name = $this->api->session()->get(AbstractMenuMember::CURRENT_MENU_NAME);
        $message = $e->getMessage();
        if (!empty($config->ttexceptions[$menu_name])) {
            $translations = $config->ttexceptions[$menu_name];
            if (empty($translations[$message])) {
                $translations = $config->ttexceptions['common'];
            }
        } else {
            $translations = $config->ttexceptions['common'];
        }
        if (!empty($translations[$message])) {
            $this->api->answer($translations[$message], $this->parse_mode, null, true, []);
        } else {
            throw $e;
        }
    }
    
    public function setParseMode($parse_mode) {
        $this->parse_mode = $parse_mode;
        
        if ($this->options) {
            foreach ($this->options as $option) {
                if (is_a($option, 'UserMenu')) {
                    $option->setParseMode($this->parse_mode);
                }
            }
        }
    }
}
