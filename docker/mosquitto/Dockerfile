FROM eclipse-mosquitto:2
COPY start.sh /start.sh
RUN chmod +x /start.sh
EXPOSE 1883 9001
ENTRYPOINT ["sh", "/start.sh"]
