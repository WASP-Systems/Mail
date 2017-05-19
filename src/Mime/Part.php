<?php
/*
This is part of Wedeto, the WEb DEvelopment TOolkit.
Wedeto\Mail is published under the BSD 3-Clause License.

Wedeto\Mail\Mime\Part was adapted from Zend\Mime\Part.
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

namespace Wedeto\Mail\Mime;

use Wedeto\Mail\MailException;

/**
 * Class representing a MIME part.
 */
class Part implements PartInterface
{
    protected $type = Mime::TYPE_OCTETSTREAM;
    protected $encoding = Mime::ENCODING_8BIT;
    protected $id;
    protected $disposition;
    protected $filename = '';
    protected $description;
    protected $charset;
    protected $boundary;
    protected $location;
    protected $language;
    protected $content;
    protected $isStream = false;
    protected $filters = [];

    /**
     * Create a new Mime Part. The (unencoded) content of the Part as passed as
     * a string or stream.
     *
     * @param mixed $content String or Stream containing the content
     * @throws \InvalidArgumentException
     */
    public function __construct($content = '')
    {
        $this->setContent($content);
    }

    /**
     * Set content type of the part
     *
     * @param string $type
     * @return Wedeto\Mail\Mime\Part Provides fluent interface
     */
    public function setType(string $type = Mime::TYPE_OCTETSTREAM)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string The content type of the part
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set encoding
     * @param string $encoding
     * @return Wedeto\Mail\Mime\Part Provides fluent interface
     */
    public function setEncoding(string $encoding = Mime::ENCODING_8BIT)
    {
        $this->encoding = $encoding;
        return $this;
    }

    /**
     * @return string The encoding
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * Set id
     * @param string $id
     * @return Wedeto\Mail\Mime\Part Provides fluent interface
     */
    public function setId(string $id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Generate id based on timestamp and filename
     * @return string The generated and set part ID
     */
    public function generateId()
    {
        $data = sha1(microtime(true) . $this->filename);
        $this->setId(substr($data, 0, 8) . $this->filename);
        return $this->getId();
    }

    /**
     * @return string The ID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set disposition
     * @param string $disposition
     * @return Wedeto\Mail\Mime\Part Provides fluent interface
     */
    public function setDisposition(string $disposition)
    {
        $this->disposition = $disposition;
        return $this;
    }

    /**
     * @return string The disposition
     */
    public function getDisposition()
    {
        return $this->disposition;
    }

    /**
     * Set description
     * @param string $description
     * @return Wedeto\Mail\Mime\Part Provides fluent interface
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string The description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set filename
     * @param string $filename
     * @return Wedeto\Mail\Mime\Part Provides fluent interface
     */
    public function setFileName(string $filename)
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * @return string The filename
     */
    public function getFileName()
    {
        return $this->filename;
    }

    /**
     * Set charset
     * @param string $charset The charset
     * @return Wedeto\Mail\Mime\Part Provides fluent interface
     */
    public function setCharset(string $charset)
    {
        $this->charset = $charset;
        return $this;
    }

    /**
     * @return string The charset of the part
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * Set boundary
     * @param string $boundary The boundary string
     * @return Wedeto\Mail\Mime\Part Provides fluent interface
     */
    public function setBoundary(string $boundary)
    {
        $this->boundary = $boundary;
        return $this;
    }

    /**
     * Get boundary
     * @return string
     */
    public function getBoundary()
    {
        return $this->boundary;
    }

    /**
     * Set location
     * @param string $location
     * @return Wedeto\Mail\Mime\Part Provides fluent interface
     */
    public function setLocation(string $location)
    {
        $this->location = $location;
        return $this;
    }

    /**
     * Get location
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set language
     * @param string $language
     * @return Wedeto\Mail\Mime\Part Provides fluent interface
     */
    public function setLanguage(string $language)
    {
        $this->language = $language;
        return $this;
    }

    /**
     * @return string The language of the part
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set content
     * @param mixed $content  String or Stream containing the content
     * @throws \InvalidArgumentException
     * @return Wedeto\Mail\Mime\Part Provides fluent interface
     */
    public function setContent($content)
    {
        if (!is_string($content) && ! is_resource($content))
        {
            throw new \InvalidArgumentException(sprintf(
                'Content must be string or resource; received "%s"',
                is_object($content) ? get_class($content) : gettype($content)
            ));
        }
        $this->content = $content;
        if (is_resource($content))
        {
            $this->isStream = true;
            $this->setEncoding(Mime::ENCODING_BASE64);
        }

        return $this;
    }

    /**
     * Set isStream
     * @param bool $isStream
     * @return Wedeto\Mail\Mime\Part Provides fluent interface
     */
    public function setIsStream(bool $isStream = false)
    {
        $this->isStream = (bool)$isStream;
        return $this;
    }

    /**
     * Set filters
     * @param array $filters
     * @return Wedeto\Mail\Mime\Part Provides fluent interface
     */
    public function setFilters(array $filters = [])
    {
        $this->filters = $filters;
        return $this;
    }

    /**
     * @return array The list of filters
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Check if this part can be read as a stream.
     * if true, getEncodedStream can be called, otherwise
     * only getContent can be used to fetch the encoded
     * content of the part
     *
     * @return bool If the part is a stream or not
     */
    public function isStream()
    {
        return $this->isStream;
    }

    /**
     * If this was created with a stream, return a filtered stream for reading
     * the content. Very useful for large file attachments.
     *
     * @param string $EOL The End-of-Line delimiter
     * @return resource The stream resource
     * @throws MailException If not a stream or unable to append filter
     */
    public function getEncodedStream(string $EOL = Mime::LINEEND)
    {
        if (!$this->isStream)
            throw new MailException('Attempt to get a stream from a string part');

        switch ($this->encoding)
        {
            case Mime::ENCODING_QUOTEDPRINTABLE:
                if (array_key_exists(Mime::ENCODING_QUOTEDPRINTABLE, $this->filters))
                    stream_filter_remove($this->filters[Mime::ENCODING_QUOTEDPRINTABLE]);

                $filter = stream_filter_append(
                    $this->content,
                    'convert.quoted-printable-encode',
                    STREAM_FILTER_READ,
                    [
                        'line-length'      => 76,
                        'line-break-chars' => $EOL
                    ]
                );

                $this->filters[Mime::ENCODING_QUOTEDPRINTABLE] = $filter;
                if (!is_resource($filter))
                    throw new MailException('Failed to append quoted-printable filter');
                break;
            case Mime::ENCODING_BASE64:
                if (array_key_exists(Mime::ENCODING_BASE64, $this->filters))
                    stream_filter_remove($this->filters[Mime::ENCODING_BASE64]);

                $filter = stream_filter_append(
                    $this->content,
                    'convert.base64-encode',
                    STREAM_FILTER_READ,
                    [
                        'line-length'      => 76,
                        'line-break-chars' => $EOL
                    ]
                );
                $this->filters[Mime::ENCODING_BASE64] = $filter;
                if (!is_resource($filter))
                    throw new MailException('Failed to append base64 filter');
                break;
            default:
        }
        return $this->content;
    }

    /**
     * Get the Content of the current Mime Part in the given encoding.
     *
     * @param string $EOL The End-of-Line delimiter
     * @return string The encoded string
     */
    public function getContent(string $EOL = Mime::LINEEND)
    {
        if ($this->isStream)
        {
            $encodedStream         = $this->getEncodedStream($EOL);
            $encodedStreamContents = stream_get_contents($encodedStream);
            $streamMetaData        = stream_get_meta_data($encodedStream);

            if (isset($streamMetaData['seekable']) && $streamMetaData['seekable'])
                rewind($encodedStream);

            return $encodedStreamContents;
        }
        return Mime::encode($this->content, $this->encoding, $EOL);
    }

    /**
     * Get the RAW unencoded content from this part
     * @return string
     */
    public function getRawContent()
    {
        if ($this->isStream)
            return stream_get_contents($this->content);

        return $this->content;
    }

    /**
     * Create and return the array of headers for this MIME part
     *
     * @param string $EOL The End-of-Line delimiter
     * @return array The list of headers
     */
    public function getHeadersArray(string $EOL = Mime::LINEEND)
    {
        $headers = array();

        $contentType = $this->type;
        if (($this->isStream || !empty($this->filename)) && empty($this->disposition))
            throw new MailException("You should provide a disposition for attachments");

        if ($this->charset)
            $contentType .= ';' . $EOL . ' charset=' . $this->charset;

        if ($this->boundary)
        {
            $contentType .= ';' . $EOL
                          . " boundary=\"" . $this->boundary . '"';
        }

        $headers[] = ['Content-Type', $contentType];

        if ($this->encoding)
            $headers[] = ['Content-Transfer-Encoding', $this->encoding];

        if ($this->id)
            $headers[]  = ['Content-ID', '<' . $this->id . '>'];

        if ($this->disposition)
        {
            if ($this->disposition === Mime::DISPOSITION_ATTACHMENT && empty($this->filename))
                throw new MailException("You should provide a filename for the attachment");

            $disposition = $this->disposition;
            if ($this->filename)
                $disposition .= '; filename="' . $this->filename . '"';
            $headers[] = ['Content-Disposition', $disposition];
        }

        if ($this->description)
            $headers[] = ['Content-Description', $this->description];

        if ($this->location)
            $headers[] = ['Content-Location', $this->location];

        if ($this->language)
            $headers[] = ['Content-Language', $this->language];

        return $headers;
    }

    /**
     * Return the headers for this part as a string
     *
     * @param string $EOL The End-of-Line delimiter
     * @return string The list of headers, combined into one string, glued
     *                together with $EOL
     */
    public function getHeaders(string $EOL = Mime::LINEEND)
    {
        $res = '';
        foreach ($this->getHeadersArray($EOL) as $header)
            $res .= $header[0] . ': ' . $header[1] . $EOL;

        return $res;
    }
}
