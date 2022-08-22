# Allow the root user to connect via remote
UPDATE mysql.user SET host='%' WHERE user='root';