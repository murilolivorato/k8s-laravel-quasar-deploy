

# users 
- email: admin@gmail.com,
- password: password

- email: editor@example.com,
- password: password

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
docker build -t ghcr.io/murilolivorato/deploy-laravel-quasar-app/frontend:v1.0.1 -f Dockerfile.frontend .

and 

docker push ghcr.io/murilolivorato/deploy-laravel-quasar-app/frontend:v1.0.1


Or one command - 

docker build --no-cache -t ghcr.io/murilolivorato/deploy-laravel-quasar-app/backend:latest -f Dockerfile.backend .

docker build --no-cache -t ghcr.io/murilolivorato/deploy-laravel-quasar-app/frontend:v1.0.1 -f Dockerfile.frontend .




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

# Debug:
lk get pods -n production
lk describe pod -n production -l app.kubernetes.io/name=laravel
lk get services -n production
lk get ingress -n production
lk logs -n production -l app.kubernetes.io/name=laravel -c app

# Clean up the old pods:
lk delete pod -n production -l app.kubernetes.io/name=laravel

# registre secret
lk create secret docker-registry ghcr-secret \
  --docker-server=ghcr.io \
  --docker-username=murilolivorato \
  --docker-password=<PASSWORD> \
  --namespace=production

lk create secret docker-registry ghcr-auth --docker-server=ghcr.io --docker-username=murilolivorato --docker-password=<PASSWORD> --docker-email=murilolivorato@gmail.com

# Then apply the updated deployment:
lk get secret ghcr-secret -n production

# Then apply the updated deployment:
lk apply -f kube-manifests/backend-deployment.yaml

# Check the pods status:
lk get pods -n production


# Delete the old deployment
lk delete deployment laravel -n production
lk delete deployment frontend -n production

# Delete any lingering pods
lk delete pod -n production -l app.kubernetes.io/name=laravel
lk delete pod -n production -l app.kubernetes.io/name=frontend


# Check the pods status:
lk get pods -n production -w

# If you see any issues, let's check the pod details:
lk describe pod -n production -l app.kubernetes.io/name=frontend

# Check the logs of the frontend pod:
lk logs -n production -l app.kubernetes.io/name=frontend

# Verify the services are properly configured:
lk get services -n production


# Delete the pods to force them to use the new configuration
lk delete pod -n production -l app.kubernetes.io/name=frontend

# Or if you have curl installed in your cluster, you can test from inside:
lk run -n production curl --rm -i --tty --image=curlimages/curl -- sh
# Then inside the pod:
curl http://frontend:80
# Test the API endpoint
curl http://frontend:80/api/health

# connection 
lk exec -it -n production postgres-5554d748d5-8rtjb -- psql -U test_user -d laravel_gke_db


# Run migrations
lk exec -it -n production laravel-bc7f7f579-6kglp -c app -- php artisan migrate

# If that doesn't work, we can try with --force
lk exec -it -n production laravel-bc7f7f579-6kglp -c app -- php artisan migrate --force

# Create a temporary pod to test the frontend service
lk run -n production curl --rm -i --tty --image=curlimages/curl -- sh
# Then inside the pod:
curl http://frontend:80

# First, let's check the Ingress controller service to get the external IP that will be used to access both frontend and backend:
lk get svc -n ingress-nginx ingress-nginx-controller

# Let's also check all services in the production namespace:
get svc -n production

# Let's verify the Ingress status to see if it got an address:
lk describe ingress -n production laravel

# delete all ingress
lk delete pod -n ingress-nginx --all



# Run migrations
-> get pod name
lk get pods -n production -l app.kubernetes.io/name=laravel
-> run command
lk exec -n production -it {pod-name} -c app -- php artisan migrate


# rollout
lk rollout restart deployment -n production frontend
lk rollout status deployment -n production frontend


# image is chached ?
add this to deployment ->
imagePullPolicy: Always

# after 
lk rollout restart deployment -n production frontend

# you can verify 
lk get pods -n production -l app.kubernetes.io/name=frontend -o wide

lk delete pod -n production frontend-5857fd7564-dv2dq
lk set image deployment/frontend -n production frontend=ghcr.io/murilolivorato/deploy-laravel-quasar-app/frontend:latest@$(docker images ghcr.io/murilolivorato/deploy-laravel-quasar-app/frontend:latest --format "{{.ID}}")



# is not updating the deploymen , even when change the image 
## Delete the deployment (this will keep the pods running)
lk delete deployment frontend -n production

## Apply the new deployment
lk apply -f kube-manifests/frontend-deployment.yaml