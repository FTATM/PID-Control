FROM eclipse-mosquitto:2
COPY start.sh /start.sh
# ตัด CR ออก เผื่อไฟล์ถูก checkout บน Windows เป็น CRLF
RUN sed -i 's/\r$//' /start.sh && chmod +x /start.sh
EXPOSE 1883 9001
ENTRYPOINT ["sh", "/start.sh"]
