<?php
/*
This is part of Wedeto, the WEb DEvelopment TOolkit.
It is published under the BSD 3-Clause License.

Wedeto\Mail\Address was adapted from Zend\Mail\Address.
The modifications are: Copyright 2017, Egbert van der Wal <wedeto at pointpro dot nl>

The original source code is copyright Zend Technologies USA Inc. The original
licence information is included below.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

Redistributions of source code must retain the above copyright notice, this
list of conditions and the following disclaimer. Redistributions in binary form
must reproduce the above copyright notice, this list of conditions and the
following disclaimer in the documentation and/or other materials provided with
the distribution. Neither the name of Zend or Rogue Wave Software, nor the
names of its contributors may be used to endorse or promote products derived
from this software without specific prior written permission. THIS SOFTWARE IS
PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR
IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO
EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
ADVISED OF THE POSSIBILITY OF SUCH DAMAGE. 
*/

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Wedeto\Mail;

use Wedeto\Mail\Mime\Mime;

/**
 * Represent an e-mail address, and convert it to a string ready for e-mail sending
 */
class Address 
{
    /** The e-mail address */
    protected $email;

    /** The name */
    protected $name;

    /**
     * Create the addres instance
     *
     * @param string $email The e-mail address
     * @param string $name The name of the recipient
     * @throws InvalidArgumentException when the e-mail address is not valid
     */
    public function __construct($email, string $name = null)
    {
        if (is_array($email))
        {
            reset($email);
            $key = key($email);
            $value = current($email);
            if (is_string($key) && is_string($value))
            {
                $email = $key;
                $name = $value;
            }
            elseif (is_string($value))
                $email = $value;
        }

        if (!is_string($email))
            throw new \InvalidArgumentException("Invalid e-mail address: " . gettype($email));

        // First check if there is a name and e-mail embedded in one string
        if (preg_match('/(.*)<([^\s]+)>/', $email, $match))
        {
            $email = $match[2];
            $name = $name ?? trim($match[1]);
        }

        $filtered = filter_var($email, FILTER_VALIDATE_EMAIL);
        if ($filtered === false && !Mime::isPrintable($email))
        {
            if (!function_exists('idn_to_ascii'))
            {
                // @codeCoverageIgnoreStart
                throw new \RuntimeException("E-mail address contains non-ASCII characters but php-idn extension is not available");
                // @codeCoverageIgnoreEnd
            }

            // Unicode needs to be punycoded
            $parts = explode('@', $email);
            if (count($parts) === 2)
            {
                $opts = IDNA_DEFAULT;
                $var = version_compare(phpversion(), '7.2.0') < 0 ? INTL_IDNA_VARIANT_2003 : INTL_IDNA_VARIANT_UTS46;
                $punycoded = $parts[0] . '@' . idn_to_ascii($parts[1], $opts, $var);
                $filtered = filter_var($punycoded, FILTER_VALIDATE_EMAIL);
            }
        }
        
        if ($filtered === false)
            throw new \InvalidArgumentException('Not a valid e-mail address: ' . $email);

        $this->email = $email;
        $this->name = preg_replace('/\p{Cc}/u', '', $name);
    }

    /**
     * @return string The e-mail address
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return string The name
     */
    public function getName()
    {
        return empty($this->name) ? null : $this->name;
    }
    
    /**
     * @return string String representation of address
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * @return string String representation of address
     */
    public function toString()
    {
        // E-mail address part
        $email = $this->getEmail();

        $opts = IDNA_DEFAULT;
        $var = version_compare(phpversion(), '7.2.0') < 0 ? INTL_IDNA_VARIANT_2003 : INTL_IDNA_VARIANT_UTS46;

        if (preg_match('/^(.+)@([^@]+)$/', $email, $matches))
        {
            $localPart = $matches[1];
            $hostname = \idn_to_ascii($matches[2], $opts, $var);
            $email = sprintf('%s@%s', $localPart, $hostname);
        }

        // Name part
        $name = $this->getName();
        if (empty($name))
            return $email;

        if (!Mime::isPrintable($name))
            $name = Mime::encode($name, 'Q', '', Header::EOL);
        elseif (strpos($name, ',') !== false)
            $name = sprintf('"%s"', str_replace('"', '\\"', $name));

        return sprintf('%s <%s>', $name, $email);
    }
}
