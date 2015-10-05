<?php

/*
 * The MIT License
 *
 * Copyright 2015 fabien.sanchez.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace fzed51\Import;

/**
 * Description of SourceFile
 * Import de fonction façon python
 * from("monfichiersource.php")->import("mafonction");
 *
 * @author fabien.sanchez
 */
class SourceFile {

    static public $history = array();
    private $fileName;
    private $tokens;

    public function __construct($filename) {
        if (file_exists($filename)) {
            $this->fileName = $filename;
            $this->scanFile();
        } else {
            throw new \Exception();
        }
    }

    private function scanFunction() {
        self::$history[$this->fileName] = [];
        for ($idTocken = 0; $idTocken < count($this->tokens); $idTocken++) {
            $token = $this->tokens[$idTocken];
            if ($token === '{') {
                $idTocken = $this->skipBrace($idTocken);
            } elseif (is_array($token)) {
                if (token_name($token[0]) == 'T_FUNCTION') {
                    $idTocken = $this->getFunction($idTocken);
                }
            }
        }
    }

    private function skipBrace($idTocken) {
        for ($idTocken = $idTocken + 1; $idTocken < count($this->tokens); $idTocken++) {
            $token = $this->tokens[$idTocken];
            if ($token === '{') {
                $idTocken = $this->skipBrace($idTocken);
            } elseif ($token === '}') {
                return $idTocken;
            }
        }
    }

    private function getFunction($idTocken) {
        $function = $this->tokens[$idTocken][1];
        $level = 0;
        $nom = null;
        $canBeStore = false;
        for ($idTocken = $idTocken + 1; $idTocken < count($this->tokens); $idTocken++) {
            $token = $this->tokens[$idTocken];

            if (is_null($nom) && token_name($token[0]) == 'T_STRING') {
                $nom = $token[1];
            }

            if ($token === '{') {
                $level++;
            } elseif (is_array($token) && $token[0] == 383) {
                $level++;
            } elseif ($token === '}') {
                $level--;
                $canBeStore = true;
            }

            if (is_array($token)) {
                $function .= $token[1];
            } else {
                $function .= $token;
            }
            if ($canBeStore && $level == 0) {
                self::$history[$this->fileName][$nom] = $function;
                return $idTocken;
            }
        }
    }

    private function scanFile() {
        if (!isset(self::$history[$this->fileName])) {
            $this->tokens = token_get_all(file_get_contents($this->fileName));
            /* for ($idToken = 0; $idToken < count($this->_tokens); $idToken++) {
              if (is_array($this->_tokens[$idToken])) {
              $this->_tokens[$idToken][] = token_name($this->_tokens[$idToken][0]);
              }
              } */
            $functions = $this->scanFunction();
        }
    }

    function import($function) {
        if (!isset(self::$history[$this->fileName][$function])) {
            throw new \Exception("La fonction '{$function}' n'est pas défine dans '{$this->fileName}'.");
        }
        // echo 'impor de : ' . PHP_EOL . htmlentities(self::$__History[$this->_fileName][$function]);
        eval(self::$history[$this->fileName][$function]);
        return $this;
    }

}
