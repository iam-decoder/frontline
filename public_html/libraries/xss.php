<?php

class Xss
{

    protected $_never_allowed_str = array(
        'document.cookie' => '[removed]',
        'document.write' => '[removed]',
        '.parentNode' => '[removed]',
        '.innerHTML' => '[removed]',
        'window.location' => '[removed]',
        '-moz-binding' => '[removed]',
        '<!--' => '&lt;!--',
        '-->' => '--&gt;',
        '<![CDATA[' => '&lt;![CDATA[',
        '<comment>' => '&lt;comment&gt;'
    ),
        $_never_allowed_regex = array(
        'javascript\s*:',
        'expression\s*(\(|&\#40;)', // CSS and IE
        'vbscript\s*:', // IE, surprise!
        'Redirect\s+302',
        "([\"'])?data\s*:[^\\1]*?base64[^\\1]*?,[^\\1]*?\\1?"
    );

    public function clean($str = null)
    {
        if (is_string($str)) {

            //remove invisible characters
            $str = $this->_removeInvisibleChars($str);

            //convert url encodings, use raw to preserve '+'
            $str = rawurldecode($str);

            //convert character entities to ASCII
            $str = preg_replace_callback("/[a-z]+=([\'\"]).*?\\1/si", array($this, '_convertAttribute'), $str);
            $str = preg_replace_callback("/<\w+.*?(?=>|<|$)/si", array($this, '_decodeEntity'), $str);

            //remove invisible chars again
            $str = $this->_removeInvisibleChars($str);

            //tabs to spaces
            if (strpos($str, "\t") !== false) {
                $str = str_replace("\t", ' ', $str);
            }

            //used later
            $converted_string = $str;

            //remove Strings that are never allowed
            $str = $this->_removeNeverAllowed($str);

            //compact exploded strings
            $words = array(
                'javascript',
                'expression',
                'vbscript',
                'script',
                'base64',
                'applet',
                'alert',
                'document',
                'write',
                'cookie',
                'window'
            );

            foreach ($words as $word) {
                $temp = '';

                for ($i = 0, $wordlen = strlen($word); $i < $wordlen; $i++) {
                    $temp .= substr($word, $i, 1) . "\s*";
                }

                // We only want to do this when it is followed by a non-word character
                // That way valid stuff like "dealer to" does not become "dealerto"
                $str = preg_replace_callback('#(' . substr($temp, 0, -3) . ')(\W)#is',
                    array($this, '_compactExplodedWords'), $str);
            }


            //remove disallowed Javascript in links or img tags
            do {
                $original = $str;

                if (preg_match("/<a/i", $str)) {
                    $str = preg_replace_callback("#<a\s+([^>]*?)(>|$)#si", array($this, '_removeJsLinks'), $str);
                }

                if (preg_match("/<img/i", $str)) {
                    $str = preg_replace_callback("#<img\s+([^>]*?)(\s?/?>|$)#si", array($this, '_removeJsImages'),
                        $str);
                }

                if (preg_match("/script/i", $str) OR preg_match("/xss/i", $str)) {
                    $str = preg_replace("#<(/*)(script|xss)(.*?)\>#si", '[removed]', $str);
                }
            } while ($original != $str);

            unset($original);

            //remove evil attributes like style or onclick
            $str = $this->_removeEvilAttributes($str);


            //sanitize naughty html elements
            $naughty = 'alert|applet|audio|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|isindex|layer|link|meta|object|plaintext|style|script|textarea|title|video|xml|xss';
            $str = preg_replace_callback('#<(/*\s*)(' . $naughty . ')([^><]*)([><]*)#is',
                array($this, '_sanitizeNaughtyHtml'),
                $str);

            //sanitize naughty scripting elements
            $str = preg_replace('#(alert|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si',
                "\\1\\2&#40;\\3&#41;", $str);

            //just in case, lets run this again.
            $str = $this->_removeNeverAllowed($str);
        }
        return $str;
    }

    protected function _convertAttribute($match)
    {
        return str_replace(array('>', '<', '\\'), array('&gt;', '&lt;', '\\\\'), $match[0]);
    }

    protected function _decodeEntity($match)
    {
        return $this->entity_decode($match[0]);
    }

    protected function _sanitizeNaughtyHtml($matches)
    {
        // encode opening brace
        $str = '&lt;' . $matches[1] . $matches[2] . $matches[3];

        // encode captured opening or closing brace to prevent recursive vectors
        $str .= str_replace(array('>', '<'), array('&gt;', '&lt;'), $matches[4]);

        return $str;
    }

    public function entity_decode($str, $charset = 'UTF-8')
    {
        if (stristr($str, '&') === false) {
            return $str;
        }

        $str = html_entity_decode($str, ENT_COMPAT, $charset);
        $str = preg_replace('~&#x(0*[0-9a-f]{2,5})~ei', 'chr(hexdec("\\1"))', $str);
        return preg_replace('~&#([0-9]{2,4})~e', 'chr(\\1)', $str);
    }

    protected function _compactExplodedWords($matches)
    {
        return preg_replace('/\s+/s', '', $matches[1]) . $matches[2];
    }

    protected function _removeJsLinks($match)
    {
        return str_replace(
            $match[1],
            preg_replace(
                '#href=.*?(alert\(|alert&\#40;|javascript\:|livescript\:|mocha\:|charset\=|window\.|document\.|\.cookie|<script|<xss|data\s*:)#si',
                '',
                $this->_filter_attributes(str_replace(array('<', '>'), '', $match[1]))
            ),
            $match[0]
        );
    }

    protected function _removeJsImages($match)
    {
        return str_replace(
            $match[1],
            preg_replace(
                '#src=.*?(alert\(|alert&\#40;|javascript\:|livescript\:|mocha\:|charset\=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si',
                '',
                $this->_filter_attributes(str_replace(array('<', '>'), '', $match[1]))
            ),
            $match[0]
        );
    }

    protected function _removeNeverAllowed($str)
    {
        $str = str_replace(array_keys($this->_never_allowed_str), $this->_never_allowed_str, $str);

        foreach ($this->_never_allowed_regex as $regex) {
            $str = preg_replace('#' . $regex . '#is', '[removed]', $str);
        }

        return $str;
    }

    protected function _removeEvilAttributes($str)
    {
        $evil_attributes = array('on\w*', 'style', 'formaction');

        do {
            $count = 0;
            $attribs = array();

            // find illegal attribute strings that have quotes (042 and 047 are octal values for quotes ' and ")
            preg_match_all('/(' . implode('|', $evil_attributes) . ')\s*=\s*(\042|\047)([^\\2]*?)(\\2)/is', $str,
                $matches, PREG_SET_ORDER);

            foreach ($matches as $attr) {
                $attribs[] = preg_quote($attr[0], '/');
            }

            // find illegal attribute strings without quotes
            preg_match_all('/(' . implode('|', $evil_attributes) . ')\s*=\s*([^\s>]*)/is', $str, $matches,
                PREG_SET_ORDER);

            foreach ($matches as $attr) {
                $attribs[] = preg_quote($attr[0], '/');
            }

            // replace illegal attribute strings that are inside an html tag
            if (count($attribs) > 0) {
                $str = preg_replace('/(<?)(\/?[^><]+?)([^A-Za-z<>\-])(.*?)(' . implode('|',
                        $attribs) . ')(.*?)([\s><]?)([><]*)/i', '$1$2 $4$6$7$8', $str, -1, $count);
            }

        } while ($count);

        return $str;
    }

    protected function _removeInvisibleChars($str, $url_encoded = true)
    {
        $non_displayables = array();

        // every control character except newline (dec 10)
        // carriage return (dec 13), and horizontal tab (dec 09)

        if ($url_encoded) {
            $non_displayables[] = '/%0[0-8bcef]/';    // url encoded 00-08, 11, 12, 14, 15
            $non_displayables[] = '/%1[0-9a-f]/';    // url encoded 16-31
        }

        $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';    // 00-08, 11, 12, 14-31, 127

        do {
            $str = preg_replace($non_displayables, '', $str, -1, $count);
        } while ($count);

        return $str;
    }
}