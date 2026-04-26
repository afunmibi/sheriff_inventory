class ApiClient {
  constructor(baseUrl = "") {
    const pathname = window.location.pathname;
    const parts = pathname.split("/").filter(Boolean);
    const apiPath = parts.slice(0, -1).join("/");
    const basePath = "/" + apiPath + "/api";

    this.baseUrl = baseUrl || basePath;
    this.token = Utils.storage.get("token");
  }

  getHeaders() {
    const headers = {
      "Content-Type": "application/json",
    };

    if (this.token) {
      headers["Authorization"] = "Bearer " + this.token;
    }

    return headers;
  }

  async request(endpoint, options = {}) {
    const url = this.baseUrl + endpoint;

    const config = {
      method: options.method || "GET",
      headers: {
        ...this.getHeaders(),
        ...options.headers,
      },
    };

    if (options.body) {
      config.body = JSON.stringify(options.body);
    }

    const retry = options.retry !== false ? 3 : 0;
    let attempts = 0;

    while (attempts <= retry) {
      try {
        const response = await fetch(url, config);
        const data = await response.json();

        if (response.status === 401) {
          this.handleUnauthorized();
          throw new Error("Session expired");
        }

        if (!response.ok) {
          throw new Error(data.message || "Request failed");
        }

        return data;
      } catch (error) {
        attempts++;
        if (attempts > retry || error.message === "Session expired") {
          throw error;
        }
      }
    }
  }

  async get(endpoint, options = {}) {
    return this.request(endpoint, { ...options, method: "GET" });
  }

  async post(endpoint, data, options = {}) {
    return this.request(endpoint, { ...options, method: "POST", body: data });
  }

  async put(endpoint, data, options = {}) {
    return this.request(endpoint, { ...options, method: "PUT", body: data });
  }

  async delete(endpoint, options = {}) {
    return this.request(endpoint, { ...options, method: "DELETE" });
  }

  handleUnauthorized() {
    Utils.storage.remove("user");
    Utils.storage.remove("token");
    window.location.href = "login.html";
  }
}

class AuthService {
  async logout() {
    Utils.storage.remove("user");
    Utils.storage.remove("token");
    return Promise.resolve();
  }
}

// Create a default instance for use in pages
const apiClient = new ApiClient();

// Export for backward compatibility
if (typeof module !== "undefined" || true) {
  window.ApiClient = ApiClient;
  window.apiClient = apiClient;
  window.AuthService = AuthService;
}
