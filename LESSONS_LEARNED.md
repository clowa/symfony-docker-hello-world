# Overview

## Lessons learned

### Symfony

- To integrate with the Symfony docker recipes, the apps `Dockerfile` has to be at the root of the project and has to contain a recipes section

  ```txt
  ###> recipes ###
  ###< recipes ###
  ```

- Symfony CLI isn't required, pure composer can be used instead - eg: `docker run -it --rm -v ./:/app -w /app composer:lts create-project symfony/skeleton:"8.0.*" my_project_directory`
- The project code has to be located at the same path in both the nginx and php-fpm containers, otherwise nginx won't be able to serve the files. In this example, we used `/var/www/html` _(instead of the nginx default `/usr/share/nginx/html`)_ as the root path for both containers.
- Symfony want's to write data to the `var/` directory.
- To see the default Symfony welcome page, the app needs to be in debug mode. This can be achieved by setting `APP_ENV=dev` and `APP_DEBUG=1` in either the one of the `.env` files or in the `compose.yaml` file.
- In prod env, Symfony should run without dev bundles, so keep `APP_ENV=prod` for the baked image.

### Docker

- Application code is only baked into the `php-fpm` container, nginx only serves static files and proxies the dynamic requests to the php-fpm container. This keeps images smaller and clean.
- Even though symfony apps can be shipped as containers it's more convenient to use the symfony dev server for local development and use containers to validate the shippable version.

## Testing the app

1. Run `docker compose up --build -d` to build and start the containers in detached mode
2. Query endpoints by using `curl` of a browser of your choice:
3. Check the container logs by running `docker compose logs` and look for the container logs to see the incoming requests
4. To stop the containers, run `docker compose down`

## Container application design

- A small JSON endpoint (like `/api/health`) is a stable healthcheck target for containers.
- Container images should be as small as possible and only contain the necessary files for their function. Use a `.dockerignore` file to exclude unnecessary files from the build context. This avoids shipping tests, docs, and local artifacts within the image.

### Purpose of each container

- `nginx`: Serves static files and proxies dynamic requests to the `php-fpm` container.
- `php-fpm`: Executes PHP code and serves the Symfony application. It is the main container that contains the application code and dependencies.

## Sources

- [Symfony - Getting Started](https://symfony.com/doc/current/setup.html)
- [Symfony - Using Docker](https://symfony.com/doc/current/setup/docker.html)
- [SLING ACADEMY - Symfony hello world](https://www.slingacademy.com/article/getting-started-with-symfony-hello-world-example/)
- [Symfony - NGINX configuration](https://symfony.com/doc/current/setup/web_server_configuration.html)
- [Docker Build Best Practices](https://docs.docker.com/build/building/best-practices/)
