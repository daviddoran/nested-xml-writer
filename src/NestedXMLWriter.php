<?php

class NestedWriter_EmptyDocumentException extends Exception {}

class NestedXMLWriter {
    /**
     * @var XMLWriter
     */
    private $writer;
    private $empty = true;

    public function __construct(XMLWriter $writer) {
        $this->writer = $writer;
    }

    public function __call($element, $args) {
        list ($prefix, $local) = self::qualify($element);

        if ($this->empty) {
            $this->empty = false;
        }

        if (empty($args) and !$this->empty) {
            $this->writer->writeElement($element);
        } else {
            $args[1] = (isset($args[1]) ? $args[1] : null);
            $attrs = (is_array($args[0]) ? $args[0] : array());
            $closure = (is_callable($args[0]) ? $args[0] : (is_callable($args[1]) ? $args[1] : null));

            $content = null;
            if (!$closure) {
                if (is_scalar($args[0]) and !is_null($args[0])) {
                    $content = $args[0];
                } else if (is_scalar($args[1]) and !is_null($args[1])) {
                    $content = $args[1];
                }
            }

            if ($prefix) {
                $this->writer->startElementNs($prefix, $local, null);
            } else {
                $this->writer->startElement($local);
            }

            if (is_array($attrs)) {
                foreach ($attrs as $attr_name => $attr_value) {
                    list($attr_prefix, $attr_local) = self::qualify($attr_name);
                    if ($attr_prefix) {
                        $this->writer->writeAttributeNs($attr_prefix, $attr_local, null, $attr_value);
                    } else {
                        $this->writer->writeAttribute($attr_local, $attr_value);
                    }
                }
            }
            if ($content) {
                $this->writer->text($content);
            } else if ($closure) {
                if (class_exists("Closure") and method_exists("Closure", "bind")) {
                    $closure = Closure::bind($closure, $this);
                }
                call_user_func($closure, $this);
            }
            $this->writer->endElement();
        }

        return $this;
    }

    public function output() {
        if ($this->empty) {
            throw new NestedWriter_EmptyDocumentException("At least one element must be added to the XML document.");
        }
        return $this->writer->outputMemory($flush = true);
    }

    /**
     * Qualify (a possibly namespaced) element or attribute name
     *
     * Usage:
     *
     * list($prefix, $local) = self::qualify("xmlns:foo");
     *
     * @param string $element
     * @return array
     */
    protected static function qualify($element) {
        $qualified = array($prefix = null, $local_part = $element);
        if (false !== ($pos = strpos($element, ":"))) {
            $qualified = array(
                $prefix = substr($element, 0, $pos),
                $local_part = substr($element, $pos + 1)
            );
        }
        return $qualified;
    }
}
