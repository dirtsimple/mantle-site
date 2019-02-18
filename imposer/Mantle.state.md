## Tweaks and Extensions for Mantle Projects

### Environment-Specific States and Blocks

If the project’s `imposer/` directory contains a `dev.state.md`, `prod.state.md`, etc., it’s automatically loaded when a matching `$WP_ENV` is in effect.

To allow environment-specific blocks of YAML, css, shell, etc., an `if-env` function is supplied.  It takes one or more arguments that are environment names, and can optionally have a first argument of `not` to indicate the block should only be used if the environment is *not* one of the ones listed.

So for example a block tagged `yaml !if-env dev` would only run if `$WP_ENV` equals `dev`, while a block tagged `shell !if-env not prod` would only run if `$WP_ENV` does *not* equal `prod`.

```shell
if [[ ${WP_ENV-} && -f "$LOCO_ROOT/imposer/$WP_ENV.state.md" ]]; then
	event on "module_loaded_imposer:project" require "$WP_ENV"
fi

if-env() {
	[[ $1 == not ]] || set -- "" "$@"
	printf -v REPLY '|%q' "${@:2}"
	echo -n "case \${WP_ENV-} in ${REPLY#|})${1:+ :;; *)}"
	echo; mdsh-block "$mdsh_lang" "$mdsh_block" "$block_start"; echo
	echo "esac"
}
```

### Automatic DB Initialization

If working with a new database, the Wordpress core may need to be installed, and sample data deleted.  This is done automatically upon `imposer apply`.   (Note that to perform an install, the active container needs to have a `WP_ADMIN_EMAIL` variable defined.  `WP_ADMIN_USER` and `WP_ADMIN_PASS` can optionally be set as well; if not defined, random values are generated, used, and echoed to the docker logs so you can find out what they are.)

```shell
event on "before_apply" mantle-initdb

mantle-initdb() {
	mantle-is-installed && return
	mantle-db-exists || event emit "mantle create db"
	event emit "mantle install db"
	mantle-is-installed && return
	[[ ${WP_ADMIN_USER-} ]]  || echo "Admin ID: ${WP_ADMIN_USER:=$(openssl rand -base64 6)}"
	[[ ${WP_ADMIN_PASS-} ]]  || echo "Password: ${WP_ADMIN_PASS:=$(openssl rand -base64 9)}"
	wp core install --skip-email --url="$WP_HOME" --title="Placeholder" \
		--admin_user="$WP_ADMIN_USER" --admin_email="$WP_ADMIN_EMAIL" \
		--admin_password="$WP_ADMIN_PASS"
	wp post delete 1 2 3 --force   # delete placeholder posts
}

mantle-db-command() { mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" "$@"; }
mantle-db-query() { echo "$@" | mantle-db-command; }
mantle-is-installed() { mantle-db-query "SHOW CREATE TABLE ${DB_PREFIX-wp_}options" >/dev/null 2>&1; }
mantle-db-exists() { mantle-db-query >/dev/null 2>&1; }
```

#### Loading From A Snapshot

If a new database needs to be created, and an `imposer/snapshot.sql` or `imposer/snapshot.sql.gz` file exists, it’s loaded into the newly-created database.

```shell
event on "mantle create db" mantle-create-db

mantle-create-db() {
	wp db create
	set -- imposer/snapshot.sql
	if [[ -f "$1" ]]; then
		mantle-db-command <"$1"
	elif [[ -f "$1.gz" ]]; then
		gunzip -c "$1.gz" | mantle-db-command
	fi
}
```

### Postmark Integration

Whenever `imposer apply` is run, we also import content from the `content/` directory, which saves a lot of PHP/Wordpress startup overhead that would happen from running it as a separate command.

```shell
require "dirtsimple/postmark"
postmark-content "content"
```

### Wordpress Tweaks

#### Post + Attachment GUIDs

To avoid changing post guids between dev, staging, and prod, we use proper UUID URNs as guids.  (This also avoids potential leakage of non-public URLs in RSS feeds.)

```php tweak
function generate_proper_guid_for_post( $data, $postarr ) {
	if ( '' === $data['guid'] ) {
		$data['guid'] = wp_slash( 'urn:uuid:' . wp_generate_uuid4() );
	}
	return $data;
}
add_filter( 'wp_insert_post_data',       'generate_proper_guid_for_post', 10, 2 );
add_filter( 'wp_insert_attachment_data', 'generate_proper_guid_for_post', 10, 2 );
```

