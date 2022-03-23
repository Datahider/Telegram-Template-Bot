<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

/**
 * Description of TopMenu
 *
 * @author drweb
 */
class TopMenu extends UserMenu {
    public function show() {
        $this->api->session()->set(AbstractMenuMember::TOP_MENU_CLASS, get_class($this));
        parent::show();
    }
    
}
