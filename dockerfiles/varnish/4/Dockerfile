FROM alpine

RUN set -xe; \
        \
        apk add --no-cache \
                varnish \
        ;

CMD ["varnishd", "-F", "-a :6081", "-f/etc/varnish/default.vcl"]