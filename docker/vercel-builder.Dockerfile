FROM almalinux:9

RUN dnf -y module enable nodejs:22 \
    && dnf -y install \
        capstone \
        git \
        libedit \
        libxcrypt-compat \
        nodejs \
        npm \
        unzip \
    && dnf clean all

WORKDIR /workspace
