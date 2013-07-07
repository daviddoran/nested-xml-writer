# Nested XML Writer

A small library providing a fluent interface to PHP's XMLWriter class.

The idea for this interface was that the combination of magic `__call` methods and anonymous functions
would allow arbitrarily nested XML to be created fluently. The result is that:

- There is only one method, the name of the element to be created;
- The common cases (element containing text, empty element, an element with children) are very simple;
- Well-formed XML is automatically output since there can be no `startElement` and `endElement` mismatches;
- Using PHP 5.4 the API is simplified further with the use of `$this` to represent the current element.

## Usage

The `NestedXMLWriter` constructor takes an XMLWriter, which will actually write the XML.

```php
# Create an in-memory XML writer (with pretty-printing)
$writer = new XMLWriter();
$writer->openMemory();
$writer->setIndent(true);

$xml = new NestedXMLWriter($writer);
```

To create an element, simply call the method of the same name:

```php
$xml->Root(); # <Root/>
```

Use the following syntax if the element name would be an invalid PHP method:

```php
$xml->{"Root-Element"}(); # <Root-Element/>
```

There are three optional parameters that can be given **in any order**:

- A scalar (e.g. `string` or `int`) sets the content of the element;
- An array (e.g. `array("src" => "image.png")`) sets the attributes of the element;
- A function gets called with the current element as the only parameter.

An element with contents:

```php
$xml->Snippet("The end."); # <Snippet>The end.</Snippet>
```

An element with attributes:

```php
$xml->Book(array("title" => "Book 1", "order" => 1)); # <Book title="Book 1" order="1"/>
```

An element with attributes and contents:

```php
$xml->Book("It all began...", array("title" => "A Story")); # <Book title="A Story">It all began...</Book>
```

An element with children:

```php
$xml->Book(array("title" => "A Story"), function ($Book) {
    $Book->Author("John Doe", array("surname" => "Doe"));
    $Book->Author("Jane Smith", array("surname" => "Smith"));
});
```

Will give the following XML:

```xml
<Book title="A Story">
    <Author surname="Doe">John Doe</Author>
    <Author surname="Smith">Jane Smith</Author>
</Book>
```

How you get the XML string out will depend on the XMLWriter used. If you used a memory writer then:

```php
echo $writer->outputMemory();
```

## Installing

Once you've [installed Composer](http://getcomposer.org/doc/00-intro.md#installation-nix) simply run:

    php composer.phar install

Or, if you installed composer globally:

    composer install

## Tests

[![Build Status](https://api.travis-ci.org/daviddoran/nested-xml-writer.png)](https://travis-ci.org/daviddoran/nested-xml-writer)

The unit tests are contained in `test` and the configuration in `phpunit.xml`.

After installing dependencies with composer, the following should run the tests:

    ./vendor/bin/phpunit

## Discussion

The usefulness of this library is severely diminished by PHP's lack of proper
closures (instead providing only anonymous functions with explicit capture).

Assuming the following array of data (and a NestedXMLWriter instance `$nestedXMLWriter`):

```php
$books = array(
    array("title" => "A Brief Guide To Books", "authors" => array("John Doe", "Alan A. Ableson")),
    array("title" => "A Briefer Guide To Books About Briefs", "authors" => array("John Boxer")),
);
```

Take, for example, the following snippet:

```php
$nestedXMLWriter->Library(function ($Library) use ($books) {
    foreach ($books as $book) {
        $Library->Book(array("title" => $book["title"]), function ($Book) use ($book) {
            foreach ($book["authors"] as $author) {
                $Book->Author($author);
            }
        });
    }
});
```

Notice that we must manually pass data down through the anonymous functions with `use (...)`.

If PHP had proper closures (à la JavaScript) then we could write this example as:

```php
$nestedXMLWriter->Library(function ($Library) {
    foreach ($books as $book) {
        $Library->Book(array("title" => $book["title"]), function ($Book) {
            foreach ($book["authors"] as $author) {
                $Book->Author($author);
            }
        });
    }
});
```

But alas, anonymous functions don't work this way in PHP.

If we're using PHP 5.4 (thanks to [Closure::bind](http://php.net/manual/en/closure.bind.php) and the [short array syntax](http://docs.php.net/manual/en/language.types.array.php#example-82)) we can write the following:

```php
$nestedXMLWriter->Library(function () use ($books) {
    foreach ($books as $book) {
        $this->Book(["title" => $book["title"]], function () use ($book) {
            foreach ($book["authors"] as $author) {
                $this->Author($author);
            }
        });
    }
});
```

Here we use `$this` to represent the current element and `[]` denotes an array.

## License

This project is released under the MIT License - see the LICENSE file for details.
