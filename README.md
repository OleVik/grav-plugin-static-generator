# Static Generator Plugin

The **Static Generator** Plugin is made for the [Grav CMS](http://github.com/getgrav/grav), and facilitates indexing and static generation of Pages.


## Installation

Installing the Static Generator plugin can be done in one of three ways: With the [Grav Package Manager (GPM)](http://learn.getgrav.org/advanced/grav-gpm) or with a zip-file.

### GPM

The simplest way to install the plugin is using the GPM through your system's terminal, also called the command line. From the root of your Grav-installation folder, type:

    bin/gpm install static-generator

This will install the plugin into the `/user/plugins`-directory within Grav. Its files can be found under `/your/grav/site/user/plugins/static-generator`.

### Manual

To install the plugin, download the zip-version of this repository and unzip it under `/your/grav/site/user/plugins`. Then rename the folder to `static-generator`. You can find these files on [GitHub](https://github.com/OleVik/grav-plugin-static-generator) or via [GetGrav.org](http://getgrav.org/downloads/plugins).

You should now have all the plugin files under

    /your/grav/site/user/plugins/static-generator

### Admin Plugin

If you use the Admin plugin, you can install it directly by browsing the `Plugins`-tab and clicking on the `Add` button.

## Configuration

Before configuring the plugin, you should copy the `user/plugins/static-generator/static-generator.yaml` to `user/config/plugins/static-generator.yaml` and only edit that copy.

Here is the default configuration and an explanation of available options:

```yaml
# Enable or disable the plugin
enabled: true
# Where to store indices: `native` for /user/plugins/static-generator/data, `persist` for /user/data/persist, `transient` for /cache/transient
index: "persist"
# Where to store static content: `native` for /user/plugins/static-generator/data, `persist` for /user/data/persist, `transient` for /cache/transient
content: "persist"
# Maximum character count for each Page
content_max_length: 100000
# Enable in Admin-plugin
admin: true
# Use plugin's JavaScript in Admin-plugin
js: true
```

Note that if you use the Admin plugin, a file with your configuration named `static-generator.yaml` will be saved in the `user/config/plugins/` folder once the configuration is saved in the Admin.

## Usage

The Static Generator Plugin does two things: Index Page(s) metadata and content, and creates static versions of Page(s).

### Indexing

If you're using the Admin plugin, an icon will be available in the quick navigation tray, in the shape of a bolt. If clicked, the plugin will start indexing the content in `/user/pages`, and each Page will have an entry. By default, all content is indexed, resulting in something like this:

```json
[
  {
    "title": "Body & Hero Classes",
    "date": "2017-08-11T12:55:00+00:00",
    "url": "http://127.0.0.1:8000/blog/hero-classes",
    "taxonomy": {
      "categories": [
        "blog"
      ],
      "tags": []
    },
    "media": [
      "unsplash-overcast-mountains.jpg"
    ],
    "content": "The [Quark theme](https://getgrav.org/downloads/themes) ...\n"
  },
  {
    "title": "Text & Typography",
    "date": "2017-07-19T11:34:00+00:00",
    "url": "http://127.0.0.1:8000/blog/text-typography",
    "taxonomy": {
      "categories": [
        "blog"
      ],
      "tags": []
    },
    "media": [
      "unsplash-text.jpg"
    ],
    "content": "The [Quark theme](https://github.com/getgrav/grav-theme-quark) ...\n"
  }
]
```

It will be wrapped for use in JavaScript, like `const GravDataIndex = [...];`. This makes it apt for use with search engines, like [FlexSearch](https://github.com/nextapps-de/flexsearch/). You can include the resulting `.js`-file and use `GravDataIndex` for searching Pages. If viewing a specific Page in Admin, for example at `http://127.0.0.1:8000/en/admin/pages/blog`, it will index the descendants of this Page in a specific-file named `blog.full.js`.

The same can be achieved through the command-line, with the command `php bin/plugin static-generator index`. See `php bin/plugin static-generator help index` for options, a normal `php bin/plugin static-generator index "/" -c` will index all Pages including content.

### Static Generation

If you want to generate static versions of the Page(s), see the `php bin/plugin static-generator page` command. This will create a `.html`-file for each Page, located in your preset `content`-location, like `/user/data/persist/static`. The folder-structure of `/user/pages` will remain intact, and assets output alongside, for example to `/user/data/persist/static/assets`. Asset-paths will be rewritten, also for media which will remain alongside each Page's `.html`-file.

For example, `php bin/plugin static-generator page "@page.descendants" "/blog"` results in:

```
\---static
    +---assets
    |   \---user
    |       \---themes
    |           \---scholar
    |               +---css
    |               |   |   print.css
    |               |   |   theme.css
    |               |   |
    |               |   \---styles
    |               |           metal.css
    |               |
    |               +---js
    |               |       theme.js
    |               |
    |               \---node_modules
    |                   +---dayjs
    |                   |   |   dayjs.min.js
    |                   |   |
    |                   |   \---plugin
    |                   |           advancedFormat.js
    |                   |
    |                   \---flexsearch
    |                       \---dist
    |                               flexsearch.min.js
    |
    \---blog
        +---classic-modern-architecture
        |       index.html
        |       unsplash-luca-bravo.jpg
        |
        +---daring-fireball-link
        |       index.html
        |       refuge-des-merveilles-tende-france---denis-degioanni-unsplashcom.jpg
        |
        +---focus-and-blur
        |       index.html
        |       unsplash-focus.jpg
        |
        +---hero-classes
        |       index.html
        |       unsplash-overcast-mountains.jpg
        |
        +---london-at-night
        |       index.html
        |       unsplash-london-night.jpg
        |       unsplash-xbrunel-johnson.jpg
        |
        +---random-thoughts
        |       index.html
        |
        +---text-typography
        |       index.html
        |       unsplash-text.jpg
        |
        \---the-urban-jungle
                index.html
                unsplash-sidney-perry.jpg                                               
```

### Cleanup

The `php bin/plugin static-generator clear` command will delete the preset folders containing indices and static content.

### Debugging

The `php bin/plugin static-generator test` command attempts to iterate Page(s) in the samme manner that the `index` and `page` commands do, to verify that the indices and static content can be generated.

## TODO

- [ ] Static Generator: Simplify options, add Page button
