# Literate DevOps for Wordpress

Mantle is a bedrock-inspired, [imposer](https://github.com/dirtsimple/imposer) and [postmark](https://github.com/dirtsimple/postmark)-based, composer-oriented, docker-compose runtime environment for revision-controlled Wordpress development, deployment, and content management.

This specific repository is the part of Mantle that runs inside a docker container: like [bedrock](https://github.com/roots/bedrock), it exists only to provide a starting point for your individual project's code.  But unlike bedrock, it includes within its layout a starting version of your [imposer-project.md](imposer-project.md) and directories for [state files](https://github.com/dirtsimple/imposer#how-state-modules-work) and [markdown content](https://github.com/dirtsimple/postmark#readme) that can be revision-controlled alongside your `composer.json`, static content, custom theme, etc.

Within an overall Mantle project, you can have one or more Mantle "sites" (Wordpress instances), creating fresh projects via e.g. `composer create-project dirtsimple/mantle-site dev` to initialize your dev site, then adding the necessary configuration to the enclosing Mantle project directory to create and run a container for it, and route web traffic to it via Traefik, an nginx proxy, or direct port mapping.

After you've created your dev site, you can turn it into a git repository, then create staging or production sites by cloning that repository, either manually or automatically (e.g. by setting the right environment for the other containers to auto-clone on creation).  When your containers start up, they'll automatically apply any state or content changes they find in the file system, transparently synchronizing changes to the database across the relevant sites.

### State and Content Management

In addition to being a convenient template for Wordpress projects, Mantle is designed to work with [imposer](https://github.com/dirtsimple/imposer) and [postmark](https://github.com/dirtsimple/postmark), automatically running `imposer apply` at container start to apply Wordpress configuration from your project source and environment variables, and `wp postmark tree content` to sync Wordpress content from Markdown files with YAML front matter.

(You can also manually run these commands to sync files at other times, or enable file watching on your development site to automatically sync with the database as you edit them.)

#### State Modules

State modules are Markdown documents (`*.state.md` files) that contain blocks of bash, jq, or PHP code, along with YAML or JSON data.  The PHP code embedded in the relevant state files is run using [wp-cli](https://wp-cli.org/), so state file code fragments have full access to the Wordpress API.

State modules are a bit like database "migrations" or Drupal "features", allowing you to expose Wordpress configuration in documented, revision-controlled files, instead of having values appear only inside various database tables.

For example, you can define a [state module](https://github.com/dirtsimple/imposer#how-state-modules-work) that reads various API keys from the container's environment and then tweaks Wordpress option values to use those keys instead of whatever was in the database before.  Or you can define modules that ensure specific plugins are installed or activated or deactivated, specific menus exist, etc.  (State modules can even include `php tweak` code snippets that get automatically combined automatically into a custom plugin, without needing to edit a theme's `functions.php`!)

See the [imposer documentation](https://github.com/dirtsimple/imposer) for more details.

#### Content Files for Posts and Pages

Any `*.md` files in the project's `content/` directory (or any subdirectory thereof) are automatically synced to create Wordpress posts or pages, converting Markdown to HTML for the post body, and using YAML front matter to set metadata like tags, dates, etc.  See the [postmark documentation](https://github.com/dirtsimple/postmark) for more details, and/or `wp help postmark`.

### Project Status

This site template is fairly stable; most future changes should revolve around updating Wordpress or other dependencies, or minor additions to the default [Mantle state file](imposer/Mantle.state.md).  This should make it easy to update your project-specific repositories with changes as needed.