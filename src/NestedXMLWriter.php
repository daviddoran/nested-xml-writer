<?php

/**
 * Class NestedWriter_EmptyDocumentException
 */
class NestedWriter_EmptyDocumentException extends Exception {}

/**
 * Class NestedXMLWriter
 */
class NestedXMLWriter {
    /**
     * @var XMLWriter
     */
    private $writer;

    public function __construct(XMLWriter $writer) {
        $this->writer = $writer;
    }

    public function __call($element, $args = array()) {
        $content = current(array_filter($args, "is_scalar"));
        $attrs = current(array_filter($args, "is_array")) ?: array();
        $closure = current(array_filter($args, "is_callable"));

        list ($prefix, $local) = self::qualify($element);

        if ($prefix) {
            $this->writer->startElementNs($prefix, $local, null);
        } else {
            $this->writer->startElement($local);
        }

        foreach ($attrs as $attr_name => $attr_value) {
            list($attr_prefix, $attr_local) = self::qualify($attr_name);
            if ($attr_prefix) {
                $this->writer->writeAttributeNs($attr_prefix, $attr_local, null, $attr_value);
            } else {
                $this->writer->writeAttribute($attr_local, $attr_value);
            }
        }

        if ($content !== false and $content !== null) {
            $this->writer->text($content);
        } else if ($closure) {
            if (class_exists("Closure") and method_exists("Closure", "bind")) {
                $closure = Closure::bind($closure, $this);
            }
            call_user_func($closure, $this);
        }

        $this->writer->endElement();

        return $this;
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
