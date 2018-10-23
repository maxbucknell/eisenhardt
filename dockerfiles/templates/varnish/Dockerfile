FROM alpine

RUN set -xe; \
        \
        apk add --no-cache \
                varnish \
        ;

COPY docker-varnish-entrypoint /usr/local/bin/
COPY default.vcl /etc/varnish/default.vcl

EXPOSE 6081

ENV HTTP_RESP_HDR_LEN 32k

CMD [ "-F", "-a :6081", "-f/etc/varnish/default.vcl" ]
ENTRYPOINT [ "docker-varnish-entrypoint" ]
