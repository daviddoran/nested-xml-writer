<?php

class NestedWriterCase extends PHPUnit_Framework_TestCase {
    /**
     * @expectedException NestedWriter_EmptyDocumentException
     */
    public function testBlankDocument() {
        $writer = $this->_new_memory_writer();
        //Following triggers exception because no elements were written
        $writer->output();
    }

    public function testEmptyRootElement() {
        $writer = $this->_new_memory_writer();
        $writer->Root();
        $this->assertEquals('<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.'<Root/>', $writer->output());
    }

    public function testStringRootElement() {
        $writer = $this->_new_memory_writer();
        $writer->Root("Test string...");
        $this->assertEquals('<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.'<Root>Test string...</Root>', $writer->output());
    }

    public function testEmptyRootElementWithAttribute() {
        $writer = $this->_new_memory_writer();
        $writer->Root(array("attr" => "Test attribute..."));
        $this->assertEquals('<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.'<Root attr="Test attribute..."/>', $writer->output());
    }

    public function testStringRootElementWithAttribute() {
        $writer = $this->_new_memory_writer();
        $writer->Root(array("attr" => "Test attribute..."), "Test string...");
        $this->assertEquals('<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.'<Root attr="Test attribute...">Test string...</Root>', $writer->output());
    }

    public function testNestedEmptyElement() {
        $writer = $this->_new_memory_writer();
        $writer->Root(array("attr" => "Test attribute..."), function ($Root) {
            $Root->Nested();
        });
        $this->assertEquals('<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.'<Root attr="Test attribute..."><Nested/></Root>', $writer->output());
    }

    public function testNestedElementWithAttribute() {
        $writer = $this->_new_memory_writer();
        $writer->Root(array("attr" => "Test attribute..."), function ($Root) {
            $Root->Nested(array("nested-attr" => "Nested test attribute..."), "Test string...");
        });
        $this->assertEquals('<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.'<Root attr="Test attribute..."><Nested nested-attr="Nested test attribute...">Test string...</Nested></Root>', $writer->output());
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

        $writer = $this->_new_memory_writer();

        $writer->Sample($namespaces, function ($Sample) {
            $Sample->{"foo:Quz"}("stuff here")
                   ->{"bar:Quz"}("stuff there");
        });

        $this->assertEquals($target, $writer->output());
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

        $writer = $this->_new_memory_writer();
        $writer->Root(function () {
            $this->Sibling1(function () {
                $this->Child1();
            });
            $this->Sibling2();
        });

        $this->assertEquals($target, $writer->output());
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
        return new NestedXMLWriter($writer);
    }
}
