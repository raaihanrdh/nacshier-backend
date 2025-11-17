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

echo "📦 Step 1: Installing dependencies..."
/usr/bin/expect << 'EOF'
set timeout 300
spawn ssh -o StrictHostKeyChecking=no ubuntu@165.154.228.4
expect "password:"
send "Ridodalopez21.\r"
expect "$ "
send "sudo apt-get update\r"
expect "$ "
send "sudo apt-get install -y docker.io docker-compose-plugin git\r"
expect "$ "
send "sudo systemctl start docker\r"
expect "$ "
send "sudo systemctl enable docker\r"
expect "$ "
send "sudo usermod -aG docker ubuntu\r"
expect "$ "
send "exit\r"
expect eof
EOF

echo "📥 Step 2: Cloning repository..."
/usr/bin/expect << 'EOF'
set timeout 300
spawn ssh -o StrictHostKeyChecking=no ubuntu@165.154.228.4
expect "password:"
send "Ridodalopez21.\r"
expect "$ "
send "cd /home/ubuntu && rm -rf nacshier-backend && git clone https://github.com/raaihanrdh/nacshier-backend.git\r"
expect "$ "
send "cd nacshier-backend\r"
expect "$ "
send "exit\r"
expect eof
EOF

echo "⚙️  Step 3: Setting up .env file..."
/usr/bin/expect << 'EOF'
set timeout 300
spawn ssh -o StrictHostKeyChecking=no ubuntu@165.154.228.4
expect "password:"
send "Ridodalopez21.\r"
expect "$ "
send "cd /home/ubuntu/nacshier-backend\r"
expect "$ "
send "cat > .env << 'ENVFILE'\nAPP_NAME=Nacshier\nAPP_ENV=production\nAPP_DEBUG=false\nAPP_URL=https://api.nacshier.my.id\nAPP_TIMEZONE=Asia/Jakarta\n\nLOG_CHANNEL=stack\nLOG_LEVEL=error\n\nDB_CONNECTION=mysql\nDB_HOST=db\nDB_PORT=3306\nDB_DATABASE=nacshier_db\nDB_USERNAME=root\nDB_PASSWORD=root123\n\nBROADCAST_DRIVER=log\nCACHE_DRIVER=file\nFILESYSTEM_DISK=local\nQUEUE_CONNECTION=sync\nSESSION_DRIVER=cookie\nSESSION_LIFETIME=120\n\nMAIL_MAILER=smtp\nMAIL_HOST=smtp.gmail.com\nMAIL_PORT=587\nMAIL_USERNAME=raihanrdh21@gmail.com\nMAIL_PASSWORD=Ridodalopez21.\nMAIL_ENCRYPTION=tls\nMAIL_FROM_ADDRESS=raihanrdh21@gmail.com\nMAIL_FROM_NAME=\"Nacshier App\"\n\nSANCTUM_STATEFUL_DOMAINS=api.nacshier.my.id\nSESSION_DOMAIN=.nacshier.my.id\n\nFRONTEND_URL=https://www.nacshier.my.id\nENVFILE\n\r"
expect "$ "
send "exit\r"
expect eof
EOF

echo "🐳 Step 4: Building Docker containers..."
/usr/bin/expect << 'EOF'
set timeout 600
spawn ssh -o StrictHostKeyChecking=no ubuntu@165.154.228.4
expect "password:"
send "Ridodalopez21.\r"
expect "$ "
send "cd /home/ubuntu/nacshier-backend\r"
expect "$ "
send "docker compose build\r"
expect "$ "
send "docker compose up -d db redis app\r"
expect "$ "
send "echo 'Waiting for database to be ready...'\r"
expect "$ "
send "sleep 20\r"
expect "$ "
send "exit\r"
expect eof
EOF

echo "🔑 Step 5: Generating APP_KEY..."
/usr/bin/expect << 'EOF'
set timeout 300
spawn ssh -o StrictHostKeyChecking=no ubuntu@165.154.228.4
expect "password:"
send "Ridodalopez21.\r"
expect "$ "
send "cd /home/ubuntu/nacshier-backend\r"
expect "$ "
send "docker exec nacshier-app php artisan key:generate\r"
expect "$ "
send "docker exec nacshier-app php artisan config:cache\r"
expect "$ "
send "exit\r"
expect eof
EOF

echo "🗄️  Step 6: Setting up database..."
/usr/bin/expect << 'EOF'
set timeout 300
spawn ssh -o StrictHostKeyChecking=no ubuntu@165.154.228.4
expect "password:"
send "Ridodalopez21.\r"
expect "$ "
send "cd /home/ubuntu/nacshier-backend\r"
expect "$ "
send "docker exec nacshier-app php artisan migrate:fresh --seed\r"
expect "$ "
send "docker exec nacshier-app php artisan storage:link\r"
expect "$ "
send "docker exec nacshier-app chmod -R 775 storage bootstrap/cache\r"
expect "$ "
send "docker exec nacshier-app chown -R www-data:www-data storage bootstrap/cache\r"
expect "$ "
send "exit\r"
expect eof
EOF

echo "🌐 Step 7: Starting Nginx..."
/usr/bin/expect << 'EOF'
set timeout 300
spawn ssh -o StrictHostKeyChecking=no ubuntu@165.154.228.4
expect "password:"
send "Ridodalopez21.\r"
expect "$ "
send "cd /home/ubuntu/nacshier-backend\r"
expect "$ "
send "docker compose up -d nginx\r"
expect "$ "
send "sleep 5\r"
expect "$ "
send "exit\r"
expect eof
EOF

echo "🔒 Step 8: Obtaining SSL certificate..."
/usr/bin/expect << 'EOF'
set timeout 300
spawn ssh -o StrictHostKeyChecking=no ubuntu@165.154.228.4
expect "password:"
send "Ridodalopez21.\r"
expect "$ "
send "cd /home/ubuntu/nacshier-backend\r"
expect "$ "
send "docker compose run --rm certbot certonly --webroot --webroot-path=/var/www/certbot --email admin@nacshier.my.id --agree-tos --no-eff-email -d api.nacshier.my.id\r"
expect "$ "
send "exit\r"
expect eof
EOF

echo "🔄 Step 9: Reloading Nginx with SSL..."
/usr/bin/expect << 'EOF'
set timeout 300
spawn ssh -o StrictHostKeyChecking=no ubuntu@165.154.228.4
expect "password:"
send "Ridodalopez21.\r"
expect "$ "
send "cd /home/ubuntu/nacshier-backend\r"
expect "$ "
send "docker compose restart nginx\r"
expect "$ "
send "docker compose up -d certbot\r"
expect "$ "
send "exit\r"
expect eof
EOF

echo "✅ Step 10: Final verification..."
/usr/bin/expect << 'EOF'
set timeout 300
spawn ssh -o StrictHostKeyChecking=no ubuntu@165.154.228.4
expect "password:"
send "Ridodalopez21.\r"
expect "$ "
send "cd /home/ubuntu/nacshier-backend\r"
expect "$ "
send "docker compose ps\r"
expect "$ "
send "docker exec nacshier-app php artisan config:show app.key\r"
expect "$ "
send "echo 'Testing API endpoint...'\r"
expect "$ "
send "curl -s -o /dev/null -w 'HTTP Status: %{http_code}\n' https://api.nacshier.my.id/ || echo 'API test completed'\r"
expect "$ "
send "exit\r"
expect eof
EOF

echo "✅ Deployment completed successfully!"
echo "🌐 API URL: https://api.nacshier.my.id"
echo "📋 Check status: ssh ubuntu@165.154.228.4 'cd /home/ubuntu/nacshier-backend && docker compose ps'"
