<?php

namespace Cheukpang;

/**
 * Class Uccode
 * @package Cheukpang
 */
class Uccode
{
    protected $uccodes;

    public function __construct()
    {
        $this->uccode = [
            'pcodecount' => -1,
            'codecount'  => 0,
            'codehtml'   => '',
        ];
    }

    /**
     * @param $code
     * @return string
     */
    public function codedisp($code)
    {
        $this->uccode['pcodecount']++;
        $code = str_replace('\\"', '"', preg_replace("/^[\n\r]*(.+?)[\n\r]*$/is", "\\1", $code));
        $this->uccode['codehtml'][$this->uccode['pcodecount']] = $this->tpl_codedisp($code);
        $this->uccode['codecount']++;

        return "[\tUCENTER_CODE_".$this->uccode['pcodecount']."\t]";
    }

    /**
     * @param $message
     * @return string
     */
    public function complie($message)
    {
        $message = htmlspecialchars($message);
        if (strpos($message, '[/code]') !== false) {
            //@todo use preg_replace_callback
            $message = preg_replace("/\s*\[code\](.+?)\[\/code\]\s*/ies", "\$this->codedisp('\\1')", $message);
        }
        if (strpos($message, '[/url]') !== false) {
            //@todo use preg_replace_callback
            $message = preg_replace(
                "/\[url(=((https?|ftp|gopher|news|telnet|rtsp|mms|callto|bctp|ed2k|thunder|synacast){1}:\/\/|www\.)([^\[\"']+?))?\](.+?)\[\/url\]/ies",
                "\$this->parseurl('\\1', '\\5')",
                $message
            );
        }
        if (strpos($message, '[/email]') !== false) {
            //@todo use preg_replace_callback
            $message = preg_replace(
                "/\[email(=([a-z0-9\-_.+]+)@([a-z0-9\-_]+[.][a-z0-9\-_.]+))?\](.+?)\[\/email\]/ies",
                "\$this->parseemail('\\1', '\\4')",
                $message
            );
        }
        $message = str_replace(
            [
                '[/color]',
                '[/size]',
                '[/font]',
                '[/align]',
                '[b]',
                '[/b]',
                '[i]',
                '[/i]',
                '[u]',
                '[/u]',
                '[list]',
                '[list=1]',
                '[list=a]',
                '[list=A]',
                '[*]',
                '[/list]',
                '[indent]',
                '[/indent]',
                '[/float]',
            ],
            [
                '</font>',
                '</font>',
                '</font>',
                '</p>',
                '<strong>',
                '</strong>',
                '<i>',
                '</i>',
                '<u>',
                '</u>',
                '<ul>',
                '<ul type="1">',
                '<ul type="a">',
                '<ul type="A">',
                '<li>',
                '</ul>',
                '<blockquote>',
                '</blockquote>',
                '</span>',
            ],
            preg_replace(
                [
                    "/\[color=([#\w]+?)\]/i",
                    "/\[size=(\d+?)\]/i",
                    "/\[size=(\d+(\.\d+)?(px|pt|in|cm|mm|pc|em|ex|%)+?)\]/i",
                    "/\[font=([^\[\<]+?)\]/i",
                    "/\[align=(left|center|right)\]/i",
                    "/\[float=(left|right)\]/i",
                ],
                [
                    "<font color=\"\\1\">",
                    "<font size=\"\\1\">",
                    "<font style=\"font-size: \\1\">",
                    "<font face=\"\\1 \">",
                    "<p align=\"\\1\">",
                    "<span style=\"float: \\1;\">",
                ],
                $message
            )
        );
        if (strpos($message, '[/quote]') !== false) {
            $message = preg_replace("/\s*\[quote\][\n\r]*(.+?)[\n\r]*\[\/quote\]\s*/is", $this->tpl_quote(), $message);
        }
        if (strpos($message, '[/img]') !== false) {
            //@todo use preg_replace_callback
            $message = preg_replace(
                [
                    "/\[img\]\s*([^\[\<\r\n]+?)\s*\[\/img\]/ies",
                    "/\[img=(\d{1,4})[x|\,](\d{1,4})\]\s*([^\[\<\r\n]+?)\s*\[\/img\]/ies",
                ],
                [
                    "\$this->bbcodeurl('\\1', '<img src=\"%s\" border=\"0\" alt=\"\" />')",
                    "\$this->bbcodeurl('\\3', '<img width=\"\\1\" height=\"\\2\" src=\"%s\" border=\"0\" alt=\"\" />')",
                ],
                $message
            );
        }
        for ($i = 0; $i <= $this->uccode['pcodecount']; $i++) {
            $message = str_replace("[\tUCENTER_CODE_$i\t]", $this->uccode['codehtml'][$i], $message);
        }

        return nl2br(
            str_replace(
                ["\t", '   ', '  '],
                ['&nbsp; &nbsp; &nbsp; &nbsp; ', '&nbsp; &nbsp;', '&nbsp;&nbsp;'],
                $message
            )
        );
    }

    /**
     * @param $url
     * @param $text
     * @return string
     */
    public function parseurl($url, $text)
    {
        if (!$url && preg_match(
                "/((https?|ftp|gopher|news|telnet|rtsp|mms|callto|bctp|ed2k|thunder|synacast){1}:\/\/|www\.)[^\[\"']+/i",
                trim($text),
                $matches
            )
        ) {
            $url = $matches[0];
            $length = 65;
            if (strlen($url) > $length) {
                $text = substr($url, 0, intval($length * 0.5)).' ... '.substr($url, -intval($length * 0.3));
            }

            return '<a href="'.(substr(
                    strtolower($url),
                    0,
                    4
                ) == 'www.' ? 'http://'.$url : $url).'" target="_blank">'.$text.'</a>';
        } else {
            $url = substr($url, 1);
            if (substr(strtolower($url), 0, 4) == 'www.') {
                $url = 'http://'.$url;
            }

            return '<a href="'.$url.'" target="_blank">'.$text.'</a>';
        }
    }

    /**
     * @param $email
     * @param $text
     * @return string
     */
    public function parseemail($email, $text)
    {
        if (!$email && preg_match("/\s*([a-z0-9\-_.+]+)@([a-z0-9\-_]+[.][a-z0-9\-_.]+)\s*/i", $text, $matches)) {
            $email = trim($matches[0]);

            return '<a href="mailto:'.$email.'">'.$email.'</a>';
        } else {
            return '<a href="mailto:'.substr($email, 1).'">'.$text.'</a>';
        }
    }

    /**
     * @param $url
     * @param $tags
     * @return mixed|string
     */
    public function bbcodeurl($url, $tags)
    {
        if (!preg_match("/<.+?>/s", $url)) {
            if (!in_array(strtolower(substr($url, 0, 6)), ['http:/', 'https:', 'ftp://', 'rtsp:/', 'mms://'])) {
                $url = 'http://'.$url;
            }

            return str_replace(['submit', 'logging.php'], ['', ''], sprintf($tags, $url, addslashes($url)));
        } else {
            return '&nbsp;'.$url;
        }
    }

    /**
     * @param $code
     * @return string
     */
    public function tpl_codedisp($code)
    {
        return '<div class="blockcode"><code id="code'.$this->uccodes['codecount'].'">'.$code.'</code></div>';
    }

    public function tpl_quote()
    {
        return '<div class="quote"><blockquote>\\1</blockquote></div>';
    }
}
