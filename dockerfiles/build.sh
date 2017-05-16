#! /usr/bin/env bash



pushd varnish/4
docker build -t maxbucknell/varnish:4 .
popd

pushd php/7.0
docker build -t maxbucknell/php:7.0 .
popd

pushd php/7.0/xdebug
docker build -t maxbucknell/php:7.0-xdebug .
popd

pushd php/7.0/alpine
docker build -t maxbucknell/php:7.0-alpine .
popd

pushd php/7.0/console
docker build -t maxbucknell/php:7.0-console .
popd

pushd php/7.0/console/xdebug
docker build -t maxbucknell/php:7.0-console-xdebug .
popd