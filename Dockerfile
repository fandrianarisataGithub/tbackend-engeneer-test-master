FROM php:8.0.17

ARG WF_UID
ARG WF_GID

RUN apt-get update && apt-get install -y \
    unzip libzip-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install zip

RUN groupadd --gid $WF_GID welfaire

RUN useradd --gid $WF_GID --uid $WF_UID \
        --shell /bin/bash \
        --create-home --home-dir /home/welfaire \
        welfaire

ENV SHELL /bin/bash
ENV COLORTERM=truecolor

USER welfaire
WORKDIR /home/welfaire

RUN echo "\n\nalias ll='ls -la'" >> ~/.bashrc
RUN mkdir ~/bin && echo '\n\nexport PATH="$HOME/bin:$PATH"' >> ~/.profile


RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php -r "if (hash_file('sha384', 'composer-setup.php') === file_get_contents('https://composer.github.io/installer.sig')) { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');" \
    && mv composer.phar ~/bin/composer

RUN mkdir ~/project
WORKDIR /home/welfaire/project
