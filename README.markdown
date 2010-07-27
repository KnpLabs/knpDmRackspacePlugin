## Configuration

/config/app.yml or /apps/front/config/app.yml

    all:
      rackspace:
        username: ~
        key: ~
        container: ~
        ttl: 3600
        url: "http://~.cdn.cloudfiles.rackspacecloud.com"
    prod:
      rackspace:
        enabled: true
    dev:
      rackspace:
        enabled: false
