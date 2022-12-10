FROM composer as composer

FROM php:{{ $.php.version }}-cli
COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY . /usr/src/golibdb
WORKDIR /usr/src/golibdb

{{- if $.php.software }}
RUN apt-get update && apt-get install -y \
{{- range $k, $need := $.php.software }}
    {{ $need }} \
{{- end}}
    && echo okay
{{- end }}




{{- if $.php.extend }} 
 {{- range $k, $ext := $.php.extend }}
 RUN docker-php-ext-install {{ $ext}} && docker-php-ext-enable {{ $ext}}
 {{- end}}
{{- end}}

{{- if $.php.run }} 
 {{- range $k, $run := $.php.run }}
 RUN {{ $run }}
 {{- end}}
{{- end}}