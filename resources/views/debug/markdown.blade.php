<x-layout class="p-8">@markdown
    # Level 1 Heading - Main Title

    This is a **paragraph** with some *emphasized text* and a [test link](https://example.com) to see how the styling looks. This paragraph should demonstrate the base text styling with proper spacing and colors.

    ## Level 2 Heading - Section Title

    Here's another paragraph that contains `inline code` to test the code styling. This paragraph also contains **strong text** and *italic text* to see how they render.

    ### Level 3 Heading - Subsection

    This section contains a blockquote:

    > This is a blockquote that should have a left border and different background. It might contain multiple sentences to test how longer quotes look with the applied styling.

    #### Level 4 Heading - Sub-subsection

    Here's an unordered list:

    - First list item with some text
    - Second list item that might be longer to test wrapping
    - Third list item with a [link inside](https://flux-ui.com)
    - Fourth item with `inline code`

    ##### Level 5 Heading - Minor Section

    And here's an ordered list:

    1. First numbered item
    2. Second numbered item with **bold text**
    3. Third numbered item with *italic text*
    4. Fourth numbered item that's longer to test how the spacing and alignment works

    ###### Level 6 Heading - Smallest Heading

    Here's a code block to test syntax highlighting preparation:

    ```php
    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;

    class Post extends Model
    {
    protected $fillable = ['title', 'content', 'published'];

    public function author()
    {
    return $this->belongsTo(User::class);
    }
    }
    ```

    ## Table Example

    Here's a table to test the table styling:

    | Name | Role | Email | Status |
    |------|------|-------|--------|
    | John Doe | Admin | john@example.com | Active |
    | Jane Smith | Editor | jane@example.com | Active |
    | Bob Johnson | Author | bob@example.com | Inactive |
    | Alice Brown | Moderator | alice@example.com | Active |

    ---

    ## Image and More Content

    Here's how an image would look:

    ![Sample Image]()

    After the image, here's another paragraph with mixed content. This paragraph contains **bold text**, *italic text*, `inline code`, and a [link to test](https://tailwindcss.com) all in one place.

    ### Another Code Example

    ```javascript
    // JavaScript example
    function handleClick(event) {
    const element = event.target;
    element.classList.toggle('active');

    // Do something with the element
    if (element.hasAttribute('data-toggle')) {
    toggleVisibility(element.dataset.toggle);
    }
    }
    ```

    ## Final Section

    This is the final paragraph to test how everything flows together. It contains various elements like **strong emphasis**, *regular emphasis*, `code snippets`, and [multiple](https://example.com) [different](https://test.com) [links](https://demo.com) to see how they all work together in the styling system.

    ### Nested Lists

    Sometimes you need nested lists:

    - Parent item one
    - Child item one
    - Child item two with `code`
    - Parent item two
    - Child item with **bold text**
    - Child item with *italic text*
    - Parent item three

    ### Mixed Content Block

    Here's a final test with mixed content:

    1. Step one with a [link](https://example.com)
    2. Step two with `inline code`
    3. Step three with **important text**

    > And a final blockquote to wrap things up. This quote contains *emphasized text* and a [link](https://example.com) to test how nested styling works within quotes.

    ---

    *End of test document*
    @endmarkdown</x-layout>
