FROM debian:jessie

RUN  apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0x5a16e7281be7a449 \
    && echo deb http://dl.hhvm.com/debian jessie main | tee /etc/apt/sources.list.d/hhvm.list \
    && apt-get update -y \
    && apt-get install hhvm -y

ENV NGINX_VERSION 1.11.8-1~jessie

RUN apt-key adv --keyserver hkp://pgp.mit.edu:80 --recv-keys 573BFD6B3D8FBC641079A6ABABF5BD827BD9BF62 \
	&& echo "deb http://nginx.org/packages/mainline/debian/ jessie nginx" >> /etc/apt/sources.list \
	&& apt-get update -y \
	&& apt-get install --no-install-recommends --no-install-suggests -y \
						ca-certificates \
						nginx=${NGINX_VERSION} \
                        nginx-module-xslt \
						nginx-module-geoip \
						nginx-module-image-filter \
						nginx-module-perl \
						nginx-module-njs \
						gettext-base \
	&& rm -rf /var/lib/apt/lists/*

# forward request and error logs to docker log collector
RUN ln -sf /dev/stdout /var/log/nginx/access.log \
	&& ln -sf /dev/stderr /var/log/nginx/error.log

# Remove default nginx configuration in lieu of h5bp.
RUN cp /etc/nginx/fastcgi_params /tmp/fastcgi_params;
RUN rm -rf /etc/nginx/;
ADD nginx/h5bp-server-config /etc/nginx/
# h5bp does not include fastcgi_params, which we need for PHP-FPM.
RUN cp /tmp/fastcgi_params /etc/nginx/fastcgi_params;

# Ensure Nginx log directory exists.
RUN mkdir -p /etc/nginx/logs/;

# Set the user that runs the Nginx process.
RUN sed -i 's/user www www/user nginx nginx/g' /etc/nginx/nginx.conf

# Add the WordPress site's Nginx configuration file.
ADD nginx/site.conf /etc/nginx/sites-available/site.conf
RUN ln -s /etc/nginx/sites-available/site.conf /etc/nginx/sites-enabled/site.conf

# Expose the application source directory inside the container.
ADD src/ /usr/src/app/
RUN chown -R www-data:www-data /usr/src/app/

CMD ["nginx", "-g", "daemon off;"]
