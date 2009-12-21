<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Copyright (c) 2007-2008 Martin Jansen
 *
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

/**
 * Parser for URI Templates
 *
 * This class implements parsing of URI Templates as defined in the IETF's
 * URI Template draft.
 *
 * @author    Martin Jansen <mj@php.net>
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD
 * @copyright 2007-2008 Martin Jansen
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/URI_Template
 * @since     Class available since release 0.1.0
 */
class URI_Template
{
    /**
     * The URI template string.
     *
     * @var string $template URI template string
     */
    protected $template = '';

    /**
     * The array containing the replacement variables.
     *
     * @var array $values Array of replacement variables
     */
    protected $values = array();

    /**
     * Constructor method
     *
     * @param string $template URI Template string
     */
    public function __construct($template)
    {
        $this->template = $template;
    }

    /**
     * Substitutes template expansions in the URI template.
     *
     * @param array $values Associative array with replacements for the 
     *                      variables in the expansions
     * @return string URI
     */
    public function substitute($values)
    {
        /* We need to assign $values to an object member because it is needed
         * in self::_substitute().
         */
        $this->values = $values;

        /* Because it is common that a template contains several replacements,
         * we do the URL encoding here instead of in _substitute.
         */
        foreach ($this->values as &$value) {
            if (is_array($value)) {
                $value = array_map('rawurlencode', $value);
            } else {
                $value = rawurlencode($value);
            }
        }

        return preg_replace_callback('~(\{[^\}]+\})~', array($this, '_substitute'), $this->template);
    }

    /**
     * Return an array containing the template variables names.
     *
     * @return array Array of template variables names
     */
    public function getTemplateVariables()
    {
        $variables = array();

        if (preg_match_all('~(\{[^\}]+\})~', $this->template, $matches)) {
            foreach ($matches[0] as $match) {
                $expansion = substr($match, 1, -1);
                list( , , $vars) = $this->parseExpansion($expansion);
                $variables = array_merge($variables, array_keys($vars));
            }
            $variables = array_values(array_unique($variables));
        }

        return $variables;
    }

    /**
     * Callback method for handling a single replacement.
     *
     * @see substitute()
     * @param array $matches Array of matched elements
     * @return string
     */
    protected function _substitute($matches)
    {
        $output = '';
        $expansion = substr($matches[0], 1, -1);
        list($op, $arg, $variables) = $this->parseExpansion($expansion);

        foreach (array_keys($variables) as $key) {
            if (isset($this->values[$key])) {
                $variables[$key] = $this->values[$key];
            }
        }

        if (!$op) {
            $output = current($variables);
        } else {
            $opname = 'operation' . ucfirst(strtolower($op));
            if (in_array($opname, get_class_methods($this))) {
                $output = $this->$opname($variables, $arg);
            }
        }

        return $output;
    }

    /**
     * Implements the 'prefix' operator.
     *
     * Adds the value of the second parameter to the beginning of the first 
     * element from the first parameter and returns the resulting string.
     * The value of the second parameter may be an array.
     *
     * @param array $variables List of variables. Only the first element is 
     *                         used.
     * @param string $arg Prefix string
     * @return string
     */
    protected function operationPrefix($variables, $arg)
    {
        $tmp = current($variables);
        if (is_array($tmp)) {
            if (count($tmp) > 0) {
                $tmp = join($arg, $tmp);
            } else {
                $tmp = '';
            }
        }
        return (empty($tmp) ? '' : $arg . $tmp);
    }

    /**
     * Implements the 'suffix' operator.
     *
     * Appends the value of the second parameter to the first element of the
     * first parameter and returns the resulting string.  The value of the
     * second parameter may be an array.
     *
     * @param array $variables List of variables. Only the first element is 
     *                         used.
     * @param string $arg String to append to the first element of $variables.
     * @return string
     */
    protected function operationSuffix($variables, $arg)
    {
        $tmp = current($variables);
        if (is_array($tmp)) {
            if (count($tmp) > 0) {
                $tmp = join($arg, $tmp);
            } else {
                $tmp = '';
            }
        }
        return (empty($tmp) ? '' : $tmp . $arg);
    }

    /**
     * Implements the 'join' operator.
     *
     * For each variable from the first parameter that is defined and 
     * non-empty create a keyvalue string that is the concatenation of the 
     * variable name, '=', and the variable value.  All elements are in turn
     * concatenated with the value of the second parameter.
     *
     * @param array $variables List of variables
     * @param string $arg Join needle
     * @return string
     */
    protected function operationJoin($variables, $arg)
    {
        $tmp = array();
        ksort($variables);
        foreach ($variables as $key => $value) {
            if (empty($value)) {
                continue;
            }

            $tmp[] = $key . '=' . $value;
        }

        return join($arg, $tmp);
    }

    /**
     * Implements the 'list' operator.
     *
     * Joins the elements of the first element of the first parameter with the
     * value of the second parameter.
     *
     * @param array $variables List of variables. Only the first element is 
     *                         used and this must be an array.
     * @param string $arg Join needle
     * @return string
     */
    protected function operationList($variables, $arg)
    {
        $tmp = current($variables);
        return (is_array($tmp) ? join($arg, $tmp) : '');
    }

    /**
     * Implements the 'opt' operator.
     *
     * If one or more variables from the first parameter are non-empty then
     * this method returns the value of the second parameter.  Otherwise an
     * empty string is returned.
     *
     * @param array $variables List of variables
     * @param string $arg Return value
     * @return string
     */
    protected function operationOpt($variables, $arg)
    {
        foreach ($variables as $value) {
            $defined = (is_array($value) ? (count($value) > 0) : !empty($value));
            if ($defined) {
                return $arg;
            }
        }

        return '';
    }

    /**
     * Implements the 'neg' operator.
     *
     * If all the variables from the first parameter are empty then this method
     * returns the value of the second parameter.  Otherwise an empty string 
     * is returned.
     *
     * @param array $variables List of variables
     * @param string $arg Return value
     * @return string
     */
    private function operationNeg($variables, $arg)
    {
        $defined = false;
        foreach ($variables as $value) {
            $defined = $defined || (!empty($value));
        }

        return (!$defined ? $arg : '');
    }

    /**
     * Parses an expansion into its components
     *
     * @see Appendix A of the URI Templates Draft 
     *      (http://bitworking.org/projects/URI-Templates/draft-gregorio-uritemplate-02.html#appendix_a)
     * @param string $expansion Expansion
     * @return array Array with three elements containing the name of the 
     *               operation, the operation argument and the variables from 
     *               the expansion
     */
    protected function parseExpansion($expansion)
    {
        if (strstr($expansion, '|')) {
            list($op, $arg, $vars) = explode('|', $expansion);
            $op = substr($op, 1);
        } else {
            $op = $arg = '';
            $vars = $expansion;
        }

        $vars = explode(',', $vars);

        $variables = array();
        foreach ($vars as $var) {
            if (strstr($var, '=')) {
                list($varname, $vardefault) = explode('=', $var);
            } else {
                $varname = $var;
                $vardefault = '';
            }

            $variables[$varname] = $vardefault;
        }

        return array($op, $arg, $variables);
    }
}
