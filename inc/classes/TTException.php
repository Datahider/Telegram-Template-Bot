<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

/**
 * Description of TTException
 *
 * @author drweb
 */
class TTException extends Exception {
    public function __construct(string $message = "", int $code = 1, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
