<?php

/*
 * This file is part of Laravel HTMLMin.
 *
 * (c) Graham Campbell <graham@alt-three.com>
 * (c) Raza Mehdi <srmk@outlook.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HTMLMin\HTMLMin\Minifiers;

/**
 * This is the blade minifier class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 * @author Trevor Fitzgerald <fitztrev@gmail.com>
 */
class BladeMinifier implements MinifierInterface
{
    /**
     * Should minification be forcefully enabled.
     *
     * @var bool
     */
    protected $force;

    /**
     * Create a new instance.
     *
     * @param bool $force
     *
     * @return void
     */
    public function __construct($force)
    {
        $this->force = $force;
    }

    /**
     * Get the minified value.
     *
     * All credit to Trevor Fitzgerald for the regex here.
     * See the original here: http://bit.ly/U7mv7a.
     *
     * @param string $value
     *
     * @return string
     */
    public function render($value)
    {
        if ($this->shouldMinify($value)) {
            /* ORIGINAL
            $replace = [
                '/<!--[^\[](.*?)[^\]]-->/s' => '',
                "/<\?php/"                  => '<?php ',
                "/\n([\S])/"                => ' $1',
                "/\r/"                      => '',
                "/\n/"                      => '',
                "/\t/"                      => ' ',
                '/ +/'                      => ' ',
            ];
            */
            
            /* EXTRA FORK */
            /*
            $replace = [
                '/<!--[^\[](.*?)[^\]]-->/s' => '',
                "/<\?php/"                  => '<?php ',
                "/\n([\S])/"                => '$1',
                "/>\n</"                    => '><',
                "/>\s+\n</"                 => '><',
                "/>\n\s+</"                 => '><',
                "/\r/"                      => '',
                "/\n/"                      => '',
                "/\t/"                      => '',
                "/ +/"                      => ' ',
            ];
            */
            /* EXTRA FORK */
            
            
            /* EXTRA POWER */
            $replace = [
                // Remove JS comments
                // https://stackoverflow.com/questions/55550890/remove-javascript-comments-using-regex-php
                '#^\s*//.+$#m'              => '',
                '/\/\*[\s\S]*?\*\/|([^:]|^)\/\/.*$/' => '',

                // https://github.com/rjsworking/laravel-html-minify
                '/^([\t ])+/m' => '',
                '/([\t ])+$/m' => '',
                '~//[a-zA-Z0-9 ]+$~m' => '',
                '/[\r\n]+([\t ]?[\r\n]+)+/s' => '',
                '/}[\r\n\t ]+/s' => '}',
                '/}[\r\n\t ]+,[\r\n\t ]+/s' => '},',
                '/\)[\r\n\t ]?{[\r\n\t ]+/s' => '){',
                '/,[\r\n\t ]?{[\r\n\t ]+/s' => ',{',
                '/\),[\r\n\t ]+/s' => '),',

                // https://github.com/rjsworking/Laravel-HTMLMin
                '/<!--[^\[](.*?)[^\]]-->/s' => '',
                "/<\?php/"                  => '<?php ',
                // "/\n([\S])/"                => '$1',
                "/>\n</"                    => '><',
                "/>\s+\n</"                 => '><',
                "/>\n\s+</"                 => '><',
                // "/\r/"                      => '',
                // "/\n/"                      => '',
                // "/\t/"                      => '',
                // "/ +/"                      => ' ',

                // From https://github.com/nckg/laravel-minify-html
                // Remove HTML comments except IE conditions
                '/(?s)<(pre|code|textarea)[^<]*>.*?<\\/(pre|code|textarea)>(*SKIP)(*F)|<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s' => '',
                // Remove comments in the form /* */
                '/(?s)<(pre|code|textarea)[^<]*>.*?<\\/(pre|code|textarea)>(*SKIP)(*F)|(?<!\S)\/\/\s*[^\r\n]*/' => '',
                // Shorten multiple white spaces
                '/(?s)<(pre|code|textarea)[^<]*>.*?<\\/(pre|code|textarea)>(*SKIP)(*F)|\s{2,}/' => ' ',
                // Remove whitespaces between HTML tags
                '/(?s)<(pre|textarea)[^<]*>.*?<\\/(pre|code|textarea)>(*SKIP)(*F)|>\s{2,}</' => '><',
                // Collapse new lines
                '/(?s)<(pre|code|textarea)[^<]*>.*?<\\/(pre|code|textarea)>(*SKIP)(*F)|(\r?\n)/' => '',
            ];
            
            
            
            
            
            $value = preg_replace(array_keys($replace), array_values($replace), $value);
        } else {
            // Where skip minification tags are used let's remove them from markdown or blade.
            $value = preg_replace("/<!--[\s]+skip\.minification[\s]+-->/", '', $value);
        }

        return $value;
    }

    /**
     * Determine if the blade should be minified.
     *
     * @param string $value
     *
     * @return bool
     */
    protected function shouldMinify($value)
    {
        if ($this->force) {
            return true;
        }

        return !$this->containsBadHtml($value) && !$this->containsBadComments($value);
    }

    /**
     * Does the code contain bad html?
     *
     * @param string $value
     *
     * @return bool
     */
    protected function containsBadHtml($value)
    {
        return preg_match('/<(code|pre|textarea)/', $value) ||
            preg_match('/<script[^\??>]*>[^<\/script>]/', $value) ||
            preg_match('/<!--[\s]+skip\.minification[\s]+-->/', $value) ||
            preg_match('/value=("|\')(.*)([ ]{2,})(.*)("|\')/', $value);
    }

    /**
     * Does the code contain bad comments?
     *
     * @param string $value
     *
     * @return bool
     */
    protected function containsBadComments($value)
    {
        foreach (token_get_all($value) as $token) {
            if (!is_array($token) || !isset($token[0]) || $token[0] !== T_COMMENT) {
                continue;
            }

            if (substr($token[1], 0, 2) === '//') {
                return true;
            }
        }

        return false;
    }
}
