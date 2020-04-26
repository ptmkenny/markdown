This module also provides the following Twig extensions for use in templates:

### Filter/Function

For simple strings or variables, you can use the `markdown` filter or
function:

Filter:
```twig
{{ "# Some Markdown"|markdown }}
{{ variableContainingMarkdown|markdown }}
```

Function:
```twig
{{ markdown("# Some Markdown") }}
{{ markdown(variableContainingMarkdown) }}
```

### Tag

If you have more than a single line of Markdown, use the `markdown` tag:

```twig
{% markdown %}
  # Some Markdown

  > This is some _simple_ **markdown** content.
{% endmarkdown %}
```

### Global

For more advanced use cases, you can use the `markdown` global for
direct access to the `MarkdownInterface` instance.

Generally speaking, it is not recommended that you use this. Doing so
will bypass any existing permissions the current user may have in
regards to filters.

However, this is particularly useful if you want to specify a specific
parser to use (if you have multiple installed):

```twig
{{ markdown.getParser('parsedown').parse("# Some Markdown") }}
{{ markdown.getParser('parsedown').parse(variableContainingMarkdown) }}
```
