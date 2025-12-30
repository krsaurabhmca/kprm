(function (global) {
  function createChatWidget(options = {}) {
    const apiKey = options.apiKey || "";
    if (!apiKey) {
      console.error("API key is required to use the chat widget.");
      return;
    }
    const apiUrl = "https://api.openai.com/v1/chat/completions";

    // === Main Container ===
    const container = document.createElement("div");
    container.style.position = "fixed";
    container.style.bottom = "80px";
    container.style.right = "20px";
    container.style.width = "360px";
    container.style.height = "500px";
    container.style.borderRadius = "12px";
    container.style.background = "#fff";
    container.style.boxShadow = "0 6px 18px rgba(0,0,0,0.25)";
    container.style.display = "none";
    container.style.flexDirection = "column";
    container.style.fontFamily = "Segoe UI, sans-serif";
    container.style.zIndex = "9999";
    container.style.overflow = "hidden";
    container.style.transition = "transform 0.3s ease, opacity 0.3s ease";
    container.style.transform = "scale(0.9)";
    container.style.opacity = "0";
    container.style.zIndex = "2147483647";

    // === Header ===
    const header = document.createElement("div");
    header.style.background = "#4CAF50";
    header.style.color = "#fff";
    header.style.padding = "12px";
    header.style.display = "flex";
    header.style.justifyContent = "space-between";
    header.style.alignItems = "center";
    header.style.fontWeight = "bold";
    header.style.fontSize = "16px";

    header.textContent = "🤖 AI Assistant";

    const closeBtn = document.createElement("span");
    closeBtn.innerHTML = "&times;";
    closeBtn.style.cursor = "pointer";
    closeBtn.style.fontSize = "20px";
    closeBtn.style.fontWeight = "bold";
    closeBtn.style.marginLeft = "10px";
    header.appendChild(closeBtn);

    // === Messages Area ===
    const messages = document.createElement("div");
    messages.style.flex = "1";
    messages.style.padding = "12px";
    messages.style.overflowY = "auto";
    messages.style.fontSize = "14px";
    messages.style.background = "#f9f9f9";

    // === Input Area ===
    const inputContainer = document.createElement("div");
    inputContainer.style.display = "flex";
    inputContainer.style.borderTop = "1px solid #ddd";

    const input = document.createElement("input");
    input.type = "text";
    input.placeholder = "Type a message...";
    input.style.flex = "1";
    input.style.border = "none";
    input.style.padding = "12px";
    input.style.fontSize = "14px";
    input.style.outline = "none";
    input.id = "chat_input";

    const sendBtn = document.createElement("button");
    sendBtn.textContent = "Send";
    sendBtn.style.border = "none";
    sendBtn.style.background = "#4CAF50";
    sendBtn.style.color = "#fff";
    sendBtn.style.padding = "0 18px";
    sendBtn.style.cursor = "pointer";
    sendBtn.style.fontWeight = "bold";

    inputContainer.appendChild(input);
    inputContainer.appendChild(sendBtn);

    container.appendChild(header);
    container.appendChild(messages);
    container.appendChild(inputContainer);
    document.body.appendChild(container);

    // === Helpers ===
    function addMessage(text, isUser) {
      const msg = document.createElement("div");
      msg.textContent = text;
      msg.style.margin = "6px 0";
      msg.style.padding = "10px 14px";
      msg.style.borderRadius = "16px";
      msg.style.maxWidth = "80%";
      msg.style.wordWrap = "break-word";
      msg.style.alignSelf = isUser ? "flex-end" : "flex-start";
      msg.style.background = isUser ? "#DCF8C6" : "#fff";
      msg.style.border = isUser ? "1px solid #cce5cc" : "1px solid #ddd";
      msg.style.boxShadow = "0 1px 3px rgba(0,0,0,0.1)";
      messages.appendChild(msg);
      messages.scrollTop = messages.scrollHeight;
    }

    async function sendMessageToAPI(message) {
      addMessage(message, true);
      try {
        const response = await fetch(apiUrl, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Authorization: "Bearer " + apiKey,
          },
          body: JSON.stringify({
            model: "gpt-4o-mini",
            messages: [
              { role: "system", content: "You are a helpful assistant." },
              { role: "user", content: message },
            ],
          }),
        });

        const data = await response.json();
        const reply = data?.choices?.[0]?.message?.content || "⚠️ No response";
        addMessage(reply, false);
      } catch (err) {
        addMessage("❌ Error: " + err.message, false);
      }
    }

    // === Events ===
    sendBtn.addEventListener("click", () => {
      if (input.value.trim()) {
        sendMessageToAPI(input.value.trim());
        input.value = "";
      }
    });

    input.addEventListener("keypress", (e) => {
      if (e.key === "Enter" && input.value.trim()) {
        sendMessageToAPI(input.value.trim());
        input.value = "";
      }
    });

    closeBtn.addEventListener("click", () => {
      widget.close();
    });

    // === Public API ===
    const widget = {
      open: () => {
        container.style.display = "flex";
        setTimeout(() => {
          container.style.transform = "scale(1)";
          container.style.opacity = "1";
        }, 10);
      },
      close: () => {
        container.style.transform = "scale(0.9)";
        container.style.opacity = "0";
        setTimeout(() => {
          container.style.display = "none";
        }, 300);
      },
      toggle: () => {
        if (container.style.display === "none" || container.style.opacity === "0") {
          widget.open();
        } else {
          widget.close();
        }
      },
    };

    return widget;
  }

  global.ChatWidget = { create: createChatWidget };
})(window);
