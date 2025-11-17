#!/bin/bash

set -e

echo "🚀 Starting deployment of NaCshier Backend..."

# Configuration
SERVER_IP="165.154.228.4"
SERVER_USER="ubuntu"
SERVER_PASS="Ridodalopez21."
PROJECT_DIR="/home/ubuntu/nacshier-backend"
DOMAIN="api.nacshier.my.id"
EMAIL="admin@nacshier.my.id"

# Function to execute commands via SSH
ssh_exec() {
    /usr/bin/expect << EOF
set timeout 300
spawn ssh -o StrictHostKeyChecking=no $SERVER_USER@$SERVER_IP
expect "password:"
send "$SERVER_PASS\r"
expect "$ "
send "$1\r"
expect "$ "
send "exit\r"
expect eof
EOF
}

echo -e "${YELLOW}📦 Step 1: Installing dependencies...${NC}"
sshpass -p "$SERVER_PASS" ssh -o StrictHostKeyChecking=no $SERVER_USER@$SERVER_IP << 'ENDSSH'
sudo apt-get update
sudo apt-get install -y docker.io docker-compose git sshpass
sudo systemctl start docker
sudo systemctl enable docker
sudo usermod -aG docker ubuntu
ENDSSH

echo -e "${YELLOW}📥 Step 2: Cloning repository...${NC}"
sshpass -p "$SERVER_PASS" ssh -o StrictHostKeyChecking=no $SERVER_USER@$SERVER_IP << ENDSSH
cd /home/ubuntu
rm -rf nacshier-backend
git clone https://github.com/raaihanrdh/nacshier-backend.git
cd $PROJECT_DIR
ENDSSH

echo -e "${YELLOW}⚙️  Step 3: Setting up .env file...${NC}"
sshpass -p "$SERVER_PASS" ssh -o StrictHostKeyChecking=no $SERVER_USER@$SERVER_IP << 'ENDSSH'
cd /home/ubuntu/nacshier-backend
cat > .env << 'ENVFILE'
APP_NAME=Nacshier
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.nacshier.my.id
APP_TIMEZONE=Asia/Jakarta

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=nacshier_db
DB_USERNAME=root
DB_PASSWORD=root123

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=cookie
SESSION_LIFETIME=120

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=raihanrdh21@gmail.com
MAIL_PASSWORD=Ridodalopez21.
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=raihanrdh21@gmail.com
MAIL_FROM_NAME="Nacshier App"

SANCTUM_STATEFUL_DOMAINS=api.nacshier.my.id
SESSION_DOMAIN=.nacshier.my.id

FRONTEND_URL=https://www.nacshier.my.id
ENVFILE
ENDSSH

echo -e "${YELLOW}🐳 Step 4: Building Docker containers...${NC}"
sshpass -p "$SERVER_PASS" ssh -o StrictHostKeyChecking=no $SERVER_USER@$SERVER_IP << 'ENDSSH'
cd /home/ubuntu/nacshier-backend
docker-compose build
docker-compose up -d db redis app
echo "Waiting for database to be ready..."
sleep 15
ENDSSH

echo -e "${YELLOW}🔑 Step 5: Generating APP_KEY...${NC}"
sshpass -p "$SERVER_PASS" ssh -o StrictHostKeyChecking=no $SERVER_USER@$SERVER_IP << 'ENDSSH'
cd /home/ubuntu/nacshier-backend
docker exec nacshier-app php artisan key:generate
docker exec nacshier-app php artisan config:cache
ENDSSH

echo -e "${YELLOW}🗄️  Step 6: Setting up database...${NC}"
sshpass -p "$SERVER_PASS" ssh -o StrictHostKeyChecking=no $SERVER_USER@$SERVER_IP << 'ENDSSH'
cd /home/ubuntu/nacshier-backend
docker exec nacshier-app php artisan migrate:fresh --seed
docker exec nacshier-app php artisan storage:link
docker exec nacshier-app chmod -R 775 storage bootstrap/cache
docker exec nacshier-app chown -R www-data:www-data storage bootstrap/cache
ENDSSH

echo -e "${YELLOW}🌐 Step 7: Starting Nginx...${NC}"
sshpass -p "$SERVER_PASS" ssh -o StrictHostKeyChecking=no $SERVER_USER@$SERVER_IP << 'ENDSSH'
cd /home/ubuntu/nacshier-backend
docker-compose up -d nginx
ENDSSH

echo -e "${YELLOW}🔒 Step 8: Obtaining SSL certificate...${NC}"
sshpass -p "$SERVER_PASS" ssh -o StrictHostKeyChecking=no $SERVER_USER@$SERVER_IP << ENDSSH
cd /home/ubuntu/nacshier-backend
docker-compose run --rm certbot certonly --webroot --webroot-path=/var/www/certbot --email $EMAIL --agree-tos --no-eff-email -d $DOMAIN
ENDSSH

echo -e "${YELLOW}🔄 Step 9: Reloading Nginx with SSL...${NC}"
sshpass -p "$SERVER_PASS" ssh -o StrictHostKeyChecking=no $SERVER_USER@$SERVER_IP << 'ENDSSH'
cd /home/ubuntu/nacshier-backend
docker-compose restart nginx
docker-compose up -d certbot
ENDSSH

echo -e "${YELLOW}✅ Step 10: Final verification...${NC}"
sshpass -p "$SERVER_PASS" ssh -o StrictHostKeyChecking=no $SERVER_USER@$SERVER_IP << 'ENDSSH'
cd /home/ubuntu/nacshier-backend
docker-compose ps
docker exec nacshier-app php artisan config:show app.key
echo "Testing API endpoint..."
curl -s -o /dev/null -w "HTTP Status: %{http_code}\n" https://api.nacshier.my.id/ || echo "API test completed"
ENDSSH

echo -e "${GREEN}✅ Deployment completed successfully!${NC}"
echo -e "${GREEN}🌐 API URL: https://api.nacshier.my.id${NC}"
echo -e "${GREEN}📋 Check status: ssh $SERVER_USER@$SERVER_IP 'cd $PROJECT_DIR && docker-compose ps'${NC}"

