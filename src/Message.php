<?php
/*
This is part of Wedeto, the WEb DEvelopment TOolkit.
Wedeto\Mail is published under the BSD 3-Clause License.

Wedeto\Mail\Message was adapted from Zend\Mail\Message.
The modifications are: Copyright 2017, Egbert van der Wal.

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

use Traversable;
use Wedeto\Mail\Mime;

class Message
{
    /** Content of the message */
    protected $body;

    /** The array of headers */
    protected $header;

    /**
     * Message encoding. Used to determine whether or not to encode headers;
     * defaults to ASCII.
     */
    protected $encoding = 'ASCII';

    /**
     * Create the message, set the date
     */
    public function __construct()
    {
        $this->header = new Header;
        $this->header->set('Date', time());
    }

    /**
     * Is the message valid?
     *
     * If we don't any From addresses, we're invalid, according to RFC2822.
     *
     * @return bool
     */
    public function isValid()
    {
        $from = $this->getFrom();
        return !empty($from);
    }

    /**
     * Set the message encoding
     *
     * @param  string $encoding
     * @return Message
     */
    public function setEncoding(string $encoding)
    {
        $this->encoding = $encoding;
        return $this;
    }

    /**
     * Get the message encoding
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    protected static $MULTIVALUED_HEADERS = array('Cc', 'Bcc', 'To', 'From', 'Reply-To');
    protected static $ENCODE_HEADERS = array('Subject');

    /**
     * Add a header
     * @param string $name The name of the header
     * @param string $value The value of the header
     * @return Message Provides fluent interface
     */
    public function addHeader(string $name, string $value)
    {
        $this->header->set($name, $value);
        return $this;
    }

    /**
     * Access headers collection
     *
     * @return array List of headers
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * Get a specific header, properly encoded if requested
     * @param string $name The header to return
     * @param bool $encode Whether to encode the value
     * @return string|null The encoded value or null if it is not set
     */
    public function getHeaderValue(string $name, bool $encode)
    {
        return $this->header->get($name, $encode ? Header::FORMAT_ENCODED : Header::FORMAT_RAW);
    }

    /**
     * Set (overwrite) From addresses
     *
     * @param mixed $address E-mail address or array of email => name pairs
     * @param string $name The recipient's name. Omit when using an array as $address
     * @return Message Provides fluent interface
     */
    public function setFrom($address, string $name = null)
    {
        $this->addAddresses('From', $address, $name, true);
        return $this;
    }

    /**
     * Add a set of addresses to the specified header
     */
    protected function addAddresses(string $header, $address, string $name = null, bool $replace = false)
    {
        if (is_string($address))
            $address = [new Address($address, $name)];

        if ($address instanceof Address)
            $address = [$address];

        if (!is_array($address) && !($address instanceof \Traversable))
            throw new MailException("Invalid address provided");
        
        foreach ($address as $key => $value)
        {
            if ($value instanceof Address)
                $this->header->addAddress($header, $value, null, !$replace);
            elseif (is_int($key))
                $this->header->addAddress($header, $value, null, !$replace);
            else
                $this->header->addAddress($header, $key, $value, !$replace);
        }

        return $this;
    }

    /**
     * Get the list of addresses for the specified header
     * @return array List of Address objects
     */
    protected function getAddresses(string $header)
    {
        $value = $this->header->getAddress($header);
        return empty($value) ? [] : $value;
    }

    /**
     * Check if a specific header contains a certain e-mail address
     * @param string $header The header to check
     * @param string $email The e-mail address to find
     */
    public function hasAddress(string $header, string $email)
    {
        $value = $this->header->getAddress($header, Header::FORMAT_RAW);
        return isset($value[$email]);
    }

    /**
     * Add a "From" address
     *
     * @param mixed $address E-mail address or array of e-mail => name pairs
     * @param string|null $name
     * @return Wedeto\Mail\Message Provides fluent interface
     */
    public function addFrom($address, string $name = null)
    {
        return $this->addAddresses('From', $address, $name, false);
    }

    /**
     * Retrieve list of From senders
     *
     * @return array Pairs of email => name pairs
     */
    public function getFrom()
    {
        return $this->getAddresses('From');
    }

    /**
     * Overwrite the address list in the To recipients
     *
     * @param mixed $address E-mail address or array of e-mail => name pairs
     * @param string|null $name
     * @return Wedeto\Mail\Message Provides fluent interface
     */
    public function setTo($address, string $name = null)
    {
        return $this->addAddresses('To', $address, $name, true);
    }

    /**
     * Add one or more addresses to the To recipients
     * Appends to the list.
     *
     * @param mixed $address E-mail address or array of e-mail => name pairs
     * @param string|null $name
     * @return Wedeto\Mail\Message Provides fluent interface
     */
    public function addTo($address, string $name = null)
    {
        return $this->addAddresses('To', $address, $name, false);
    }

    /**
     * Access the address list of the To header
     *
     * @return array Pairs of email => name pairs
     */
    public function getTo()
    {
        return $this->getAddresses('To');
    }

    /**
     * Set (overwrite) CC addresses
     *
     * @param mixed $address E-mail address or array of e-mail => name pairs
     * @param string|null $name
     * @return Wedeto\Mail\Message Provides fluent interface
     */
    public function setCC($address, $name = null)
    {
        return $this->addAddresses('Cc', $address, $name, true);
    }

    /**
     * Add a "Cc" address
     *
     * @param mixed $address E-mail address or array of e-mail => name pairs
     * @param string|null $name
     * @return Wedeto\Mail\Message Provides fluent interface
     */
    public function addCC($address, $name = null)
    {
        return $this->addAddresses('Cc', $address, $name, false);
    }

    /**
     * Retrieve list of CC recipients
     *
     * @return array Pairs of email => name pairs
     */
    public function getCC()
    {
        return $this->getAddresses('Cc');
    }

    /**
     * Set (overwrite) BCC addresses
     *
     * @param mixed $address E-mail address or array of e-mail => name pairs
     * @param string|null $name
     * @return Wedeto\Mail\Message Provides fluent interface
     */
    public function setBCC($address, $name = null)
    {
        return $this->addAddresses('Bcc', $address, $name, true);
    }

    /**
     * Add a "Bcc" address
     *
     * @param mixed $address E-mail address or array of e-mail => name pairs
     * @param string|null $name
     * @return Wedeto\Mail\Message Provides fluent interface
     */
    public function addBCC($address, $name = null)
    {
        return $this->addAddresses('Bcc', $address, $name, false);
    }

    /**
     * Retrieve list of BCC recipients
     *
     * @return array Pairs of email => name pairs
     */
    public function getBCC()
    {
        return $this->getAddresses('Bcc');
    }

    /**
     * Overwrite the address list in the Reply-To recipients
     *
     * @param mixed $address E-mail address or array of e-mail => name pairs
     * @param string|null $name
     * @return Wedeto\Mail\Message Provides fluent interface
     */
    public function setReplyTo($address, $name = null)
    {
        return $this->addAddresses('Reply-To', $address, $name, true);
    }

    /**
     * Add one or more addresses to the Reply-To recipients
     * Appends to the list.
     *
     * @param mixed $address E-mail address or array of e-mail => name pairs
     * @param string|null $name
     * @return Wedeto\Mail\Message Provides fluent interface
     */
    public function addReplyTo($address, $name = null)
    {
        return $this->addAddresses('Reply-To', $address, $name, false);
    }

    /**
     * Access the address list of the Reply-To header
     *
     * @return array Pairs of email => name pairs
     */
    public function getReplyTo()
    {
        return $this->getAddresses('Reply-To');
    }

    /**
     * Set the envelope sender of the e-mail
     *
     * @param mixed $address E-mail address of the sender
     * @param string|null $name The name of the sender
     * @return Wedeto\Mail\Message Provides fluent interface
     */
    public function setSender(string $address, string $name = null)
    {
        return $this->addAddresses('Sender', $address, $name, true);
    }

    /**
     * Retrieve the sender address, if any
     *
     * @return Address The envelope sender or null
     */
    public function getSender()
    {
        $sender = $this->getAddresses('Sender');
        foreach ($sender as $address)
            return $address;
        return null;
    }

    /**
     * Set the message subject header value
     *
     * @param string $subject The subject ot set
     * @return Wedeto\Mail\Message Provides fluent interface
     */
    public function setSubject(string $subject)
    {
        $this->header->set('Subject', $subject);
        return $this;
    }

    /**
     * @return string The subject of the message. Null if not set
     */
    public function getSubject()
    {
        return $this->header->get('Subject');
    }

    /**
     * Set the message body
     *
     * @param string|\Wedeto\Mail\Mime\Message $body The message body
     * @throws MailException When an invalid type is provided
     * @return Wedeto\Mail\Message Provides fluent interface
     */
    public function setBody($body)
    {
        if (!is_string($body) && $body !== null)
        {
            if (!is_object($body))
            {
                throw new MailException(sprintf(
                    '%s expects a string or object argument; received "%s"',
                    __METHOD__,
                    gettype($body)
                ));
            }
            if (!$body instanceof Mime\Message)
            {
                if (!method_exists($body, '__toString'))
                {
                    throw new MailException(sprintf(
                        '%s expects object arguments of type Wedeto\Mail\Mime\Message or implementing __toString();'
                        . ' object of type "%s" received',
                        __METHOD__,
                        get_class($body)
                    ));
                }
                $body = (string)$body;
            }
        }
        $this->body = $body;

        if (!$this->body instanceof Mime\Message)
            return $this;

        // Get headers, and set Mime-Version header
        $this->addHeader('Mime-Version', '1.0');

        // Multipart content headers
        if ($this->body->isMultiPart())
        {
            $mime = $this->body->getMime();
            $this->header->setContentType('multipart/mixed; boundary="' . $mime->boundary() . '"');
            return $this;
        }

        // MIME single part headers
        $parts = $this->body->getParts();
        if (!empty($parts))
        {
            $part = array_shift($parts);
            foreach ($part->getHeadersArray(Header::EOL) as $vals)
            {
                $name = $vals[0];
                $value = $vals[1];
                $this->addHeader($vals[0], $vals[1]);
            }
        }
        return $this;
    }

    /**
     * Return the currently set message body
     *
     * @return string|Wedeto\Mail\Mime\Message
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Get the string-serialized message body text
     *
     * @return string The body text
     */
    public function getBodyText()
    {
        if ($this->body instanceof Mime\Message)
            return $this->body->generateMessage(Header::EOL);

        return (string)$this->body;
    }

    /**
     * Serialize to string
     *
     * @return string The full e-mail message
     */
    public function toString()
    {
        $header = $this->header->toString();
        return $header
               . Header::EOL
               . $this->getBodyText();
    }
}
