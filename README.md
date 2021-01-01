# Configuration for new project
1. Setup **Makefile**: `ALIAS`(alias from Makefile is auto update in .docker/.env after run `make build`).
2. Setup **Makefile**:`BUILD_IMAGE_CLI`, `BUILD_IMAGE_FPM`.
3. Remove unnecessary services from `docker-compose`.

#### How to use it after configuration
```shell script
# To build image
make build

# To start image
make up

# To go to application console
make console

# To clean up
make clean 
```

###### If your image need ssh-key to get package from company repository
uncomment this in Makefile, and comment previous target "build-prod"
```bash
#build-prod:	## Build prod image with private key
#	@docker build -t $(IMAGE)-cli:$(TAG)                       \
#		-t $(IMAGE)-cli:latest                                 \
#		--build-arg BASE_IMAGE=$(REGISTRY)/$(BASE_IMAGE_FPM)   \
#       --build-arg SSH_PRIVATE_KEY="${PRIVATE_KEY}" .
```

next, build your production image `make build && make build-prod`

