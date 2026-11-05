# Scheduler HRIS — pilih salah satu.
#
# 1) Cron (paling sederhana): tambahkan ke crontab user aplikasi.
#    Jalankan `crontab -e` lalu tambahkan baris berikut (ganti path):
#
#    * * * * * cd /var/www/hris && php artisan schedule:run >> /dev/null 2>&1
#
#
# 2) systemd service + timer (alternatif tanpa cron).
#    Simpan sebagai /etc/systemd/system/hris-scheduler.service
#
# [Unit]
# Description=HRIS Scheduler
# After=network.target
#
# [Service]
# Type=oneshot
# User=www-data
# WorkingDirectory=/var/www/hris
# ExecStart=/usr/bin/php /var/www/hris/artisan schedule:run
#
# Lalu buat /etc/systemd/system/hris-scheduler.timer:
#
# [Unit]
# Description=Jalankan HRIS scheduler setiap menit
#
# [Timer]
# OnCalendar=*:0
# Persistent=true
#
# [Install]
# WantedBy=timers.target
#
# Aktifkan:
#   sudo systemctl enable --now hris-scheduler.timer
