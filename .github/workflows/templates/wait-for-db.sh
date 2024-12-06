retries=10
count=0

while [ $count -lt $retries ]; do
  mysql -uroot -e "SELECT 1" > /dev/null 2>&1
  if [ $? -eq 0 ]; then
    echo "MySQL is available."
    break
  fi
  count=$((count+1))
  echo "MySQL is not available. Retrying..."
  sleep 1
done

if [ $count -eq $retries ]; then
  echo "Could not connect to MySQL after $retries retries."
  exit 1;
fi

echo "Now connecting to Elasticsearch"
retries=20
count=0

while [ $count -lt $retries ]; do
  if curl -s -f -o /dev/null "http://localhost:9200"; then
    echo "Elasticsearch is available."
    break
  else
    count=$((count+1))
    echo "Attempt $count of $retries: Elasticsearch is not available. Retrying..."
    sleep 1
  fi
done

if [ $count -eq $retries ]; then
  cat /var/log/elasticsearch/elasticsearch.log
  echo "Could not connect to Elasticsearch after $retries retries."
fi
