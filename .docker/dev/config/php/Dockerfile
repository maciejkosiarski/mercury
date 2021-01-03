ARG BASE_IMAGE
FROM ${BASE_IMAGE}

ARG COMPOSER_VERSION=2.0.7

RUN apt-get update && apt-get install -y zsh vim figlet git
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --version=$COMPOSER_VERSION && \
    curl -OL https://squizlabs.github.io/PHP_CodeSniffer/phpcs.phar && \
    cp phpcs.phar /usr/local/bin/phpcs && \
    chmod +x /usr/local/bin/phpcs && \
    curl -OL https://cs.symfony.com/download/php-cs-fixer-v2.phar && \
    cp php-cs-fixer-v2.phar /usr/local/bin/phpcsfixer && \
    chmod +x /usr/local/bin/phpcsfixer && \
    curl -OL https://github.com/phpmd/phpmd/releases/download/2.7.0/phpmd.phar && \
    cp phpmd.phar /usr/local/bin/phpmd && \
    chmod +x /usr/local/bin/phpmd && \
    curl -OL https://squizlabs.github.io/PHP_CodeSniffer/phpcbf.phar && \
    cp phpcbf.phar /usr/local/bin/phpcbf && \
    chmod +x /usr/local/bin/phpcbf && \
    phpcs --config-set default_standard PSR2 && \
    phpcs --config-set report_format summary && \
    phpcs --config-set show_progress 1 && \
    phpcs --config-set colors 1 && \
    pecl install xdebug && \
    docker-php-ext-enable xdebug && \
    cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini && \
    rm -rf /var/lib/apt/lists/* && \
    pecl clear-cache && \
    pecl config-set php_ini /usr/local/etc/php/conf.d/over.ini


RUN echo "su - developer" > /root/.bashrc

# --- bat --- #
RUN bash -c "curl -OL https://github.com/sharkdp/bat/releases/download/v0.15.4/bat_0.15.4_amd64.deb && dpkg -i bat_0.15.4_amd64.deb && rm bat_0.15.4_amd64.deb"

# --- awesome zsh section :) --- #
ARG DEVELOPER_UID
RUN useradd -m -s /bin/zsh -u ${DEVELOPER_UID} developer
USER developer
RUN bash -c "$(curl -fsSL https://raw.githubusercontent.com/robbyrussell/oh-my-zsh/master/tools/install.sh)" "" --unattended
RUN bash -c "git clone https://github.com/zsh-users/zsh-autosuggestions ~/.oh-my-zsh/custom/plugins/zsh-autosuggestions"
RUN echo '\
export ZSH=/home/developer/.oh-my-zsh\n\n\
export PHP_IDE_CONFIG=serverName=developer.local\n\
DISABLE_AUTO_UPDATE="true"\n\
DISABLE_UPDATE_PROMPT="true"\n\
# setup theme #\
ZSH_THEME="ys"\n\n\
plugins=(git zsh-autosuggestions)\n\
source $ZSH/oh-my-zsh.sh\n\
alias cat=bat\n\
alias clear="clear; figlet lucy-app"\n\
alias sf="php bin/console"\n\
clear\
' > /home/developer/.zshrc

WORKDIR "/application"
