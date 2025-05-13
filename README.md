
## Run those commands
- docker-compose up -d --build
- docker-compose run --rm composer install
- docker-compose run --rm artisan key:generate


## DEPLOY

# Create the repository
gcloud artifacts repositories create gke-deploy \
--repository-format=docker \
--location=us-central1 \
--description="Repository for GKE deploy artifacts"


# check region 
gcloud config list

# TAG THE IMAGES
docker build -t kube-laravel-app:v3 -f DockerFile.prod .
docker build -t kube-laravel-app:v4 -f DockerFile.prod .

docker build --no-cache -t ghcr.io/murilolivorato/deploy-laravel-quasar-app/backend:latest -f Dockerfile.backend .
docker build --no-cache -t ghcr.io/murilolivorato/deploy-laravel-quasar-app/frontend:latest -f Dockerfile.frontend .


# LIST IMAGE
docker images | grep kube-laravel-app

# TEST IMAGE
docker run -p 80:80 kube-laravel-app:v4
docker run -p 8080:8080 laravel_gcr_deploy:test


# Authenticate with Google Cloud
gcloud auth configure-docker

# Push the images to GCR
docker push us-central1-docker.pkg.dev/curso-gcp-420816/gke-deploy/laravel_gke_deploy:v9


# commands
lk apply -f kube-manifests/namespaces.yaml
lk apply -f kube-manifests/


# Check pods in production namespace
lk get pods -n production

# Check pods in monitoring namespace
lk get pods -n monitoring

# Check services
lk get services -n production

# Check ingress
lk get ingress -n production