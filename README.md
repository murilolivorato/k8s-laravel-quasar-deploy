
# DEPLOY LARAVEL GKE



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

# TAG THE IMAGES FOR GCR
docker build -t us-central1-docker.pkg.dev/curso-gcp-420816/gke-deploy/laravel_gke_deploy:v5 -f DockerFile.prod .
# LIST IMAGE
docker images | grep laravel_gke_deploy

# Authenticate with Google Cloud
gcloud auth configure-docker

# Push the images to GCR
docker push us-central1-docker.pkg.dev/curso-gcp-420816/gke-deploy/laravel_gke_deploy:v5
