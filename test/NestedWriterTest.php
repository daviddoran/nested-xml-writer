<?php

class NestedWriterCase extends PHPUnit_Framework_TestCase {
    public function testEmptyRootElement() {
        list($writer, $xml) = $this->_new_memory_writer();
        $xml->Root();
        $this->assertEquals('<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.'<Root/>', $writer->outputMemory());
    }

    public function testStringRootElement() {
        list($writer, $xml) = $this->_new_memory_writer();
        $xml->Root("Test string...");
        $this->assertEquals('<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.'<Root>Test string...</Root>', $writer->outputMemory());
    }

    public function testEmptyRootElementWithAttribute() {
        list($writer, $xml) = $this->_new_memory_writer();
        $xml->Root(array("attr" => "Test attribute..."));
        $this->assertEquals('<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.'<Root attr="Test attribute..."/>', $writer->outputMemory());
    }

    public function testStringRootElementWithAttribute() {
        list($writer, $xml) = $this->_new_memory_writer();
        $xml->Root(array("attr" => "Test attribute..."), "Test string...");
        $this->assertEquals('<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.'<Root attr="Test attribute...">Test string...</Root>', $writer->outputMemory());
    }

    public function testNestedEmptyElement() {
        list($writer, $xml) = $this->_new_memory_writer();
        $xml->Root(array("attr" => "Test attribute..."), function ($Root) {
            $Root->Nested();
        });
        $this->assertEquals('<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.'<Root attr="Test attribute..."><Nested/></Root>', $writer->outputMemory());
    }

    public function testNestedElementWithAttribute() {
        list($writer, $xml) = $this->_new_memory_writer();
        $xml->Root(array("attr" => "Test attribute..."), function ($Root) {
            $Root->Nested(array("nested-attr" => "Nested test attribute..."), "Test string...");
        });
        $this->assertEquals('<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.'<Root attr="Test attribute..."><Nested nested-attr="Nested test attribute...">Test string...</Nested></Root>', $writer->outputMemory());
    }

    public function testArgumentOrderIndifference() {
        list($writer, $xml) = $this->_new_memory_writer();
        $xml->Root(function ($Root) {
            $Root->Empty();
            $Root->String("String");
            $Root->Attrs(array("one" => "one", "two" => "two"));
            $Root->Attrs("String", array("one" => "one", "two" => "two"));
            $Root->Attrs(array("one" => "one", "two" => "two"), "String");
            $Root->NotEmpty(function ($NotEmpty) {
                $NotEmpty->Empty();
            });
            $Root->NotEmpty(array("one" => "one"), function ($NotEmpty) {
                $NotEmpty->Empty();
            });
            $Root->NotEmpty(function ($NotEmpty) {
                $NotEmpty->Empty();
            }, array("one" => "one"));
        });

        $target =
            '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL .
            '<Root>' .
                '<Empty/>' .
                '<String>String</String>' .
                '<Attrs one="one" two="two"/>' .
                '<Attrs one="one" two="two">String</Attrs>' .
                '<Attrs one="one" two="two">String</Attrs>' .
                '<NotEmpty><Empty/></NotEmpty>' .
                '<NotEmpty one="one"><Empty/></NotEmpty>' .
                '<NotEmpty one="one"><Empty/></NotEmpty>' .
            '</Root>';

        $this->assertEquals($target, $writer->outputMemory());
    }

    public function testNamespacedDocument() {
        $target =
        '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL .
        '<Sample xmlns:foo="http://foo.org/ns/foo#" xmlns:bar="http://foo.org/ns/bar#">' .
            '<foo:Quz>stuff here</foo:Quz>' .
            '<bar:Quz>stuff there</bar:Quz>' .
        '</Sample>';

        $namespaces = array(
            "xmlns:foo" => "http://foo.org/ns/foo#",
            "xmlns:bar" => "http://foo.org/ns/bar#"
        );

        list($writer, $xml) = $this->_new_memory_writer();

        $xml->Sample($namespaces, function ($Sample) {
            $Sample->{"foo:Quz"}("stuff here")
                   ->{"bar:Quz"}("stuff there");
        });

        $this->assertEquals($target, $writer->outputMemory());
    }

    /**
     * In PHP >= 5.4 we can use $this to represent the current element
     */
    public function testBindingThis() {
        if (!version_compare(PHP_VERSION, '5.4.0', '>=')) {
            $this->markTestSkipped("Test skipped because binding \$this is only supported in PHP >= 5.4");
            return;
        }

        $target =
            '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL .
            '<Root>' .
                '<Sibling1>' .
                    '<Child1/>' .
                '</Sibling1>' .
                '<Sibling2/>' .
            '</Root>';

        list($writer, $xml) = $this->_new_memory_writer();
        $xml->Root(function () {
            $this->Sibling1(function () {
                $this->Child1();
            });
            $this->Sibling2();
        });

        $this->assertEquals($target, $writer->outputMemory());
    }

    /**
     * @return NestedXMLWriter
     */
    private function _new_memory_writer() {
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(false);
        $writer->setIndentString("");
        $writer->startDocument("1.0", "UTF-8");
        return array($writer, new NestedXMLWriter($writer));
    }
}
