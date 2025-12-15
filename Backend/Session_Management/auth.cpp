// auth.cpp
// Compile: g++ auth.cpp -o auth_exec -lmysqlclient -lcrypt
// Usage:
// ./auth_exec <dbhost> <dbuser> <dbpass> <dbname> <username> <password>

#include <mysql/mysql.h>
#include <iostream>
#include <sstream>
#include <string>
#include <cstring>
#include <memory>
#include <crypt.h> // for crypt() on many systems; if not present, use <unistd.h> & link -lcrypt

class DBConnection {
private:
    static DBConnection* instance;
    MYSQL* conn;
    DBConnection(): conn(nullptr) {}
public:
    static DBConnection* getInstance() {
        if (!instance) instance = new DBConnection();
        return instance;
    }
    bool connect(const char* host, const char* user, const char* pass, const char* db, unsigned int port = 0) {
        if (conn) return true;
        conn = mysql_init(nullptr);
        if (!conn) return false;
        if (!mysql_real_connect(conn, host, user, pass, db, port, nullptr, 0)) {
            mysql_close(conn);
            conn = nullptr;
            return false;
        }
        return true;
    }
    MYSQL* get() { return conn; }
    ~DBConnection() {
        if (conn) mysql_close(conn);
    }
};
DBConnection* DBConnection::instance = nullptr;

class AuthManager {
private:
    static AuthManager* instance;
    AuthManager() {}
public:
    static AuthManager* getInstance() {
        if (!instance) instance = new AuthManager();
        return instance;
    }

    // Returns "OK", "NOT_FOUND", or "FAIL"
    std::string authenticate(MYSQL* conn, const std::string& username, const std::string& password) {
        if (!conn) return "FAIL";

        // Escape username
        char esc_user[512];
        unsigned long ulen = username.size();
        if (ulen > 500) return "FAIL";
        mysql_real_escape_string(conn, esc_user, username.c_str(), ulen);

        std::ostringstream q;
        // Player table stores users according to your schema
        q << "SELECT password FROM Player WHERE username = '" << esc_user << "' LIMIT 1;";

        if (mysql_query(conn, q.str().c_str())) {
            return "FAIL";
        }
        MYSQL_RES* res = mysql_store_result(conn);
        if (!res) return "FAIL";

        MYSQL_ROW row = mysql_fetch_row(res);
        if (!row) {
            mysql_free_result(res);
            return "NOT_FOUND";
        }
        std::string dbhash = row[0] ? row[0] : "";
        mysql_free_result(res);

        if (dbhash.empty()) return "FAIL";

        // Use crypt to verify. crypt(password, saltFromHash) returns hash if matches.
        // Many systems: crypt() supports bcrypt when glibc/libxcrypt provides it.
        char* crypt_res = crypt(const_cast<char*>(password.c_str()), dbhash.c_str());
        if (!crypt_res) return "FAIL";

        std::string computed(crypt_res);
        if (computed == dbhash) return "OK";
        return "FAIL";
    }
};
AuthManager* AuthManager::instance = nullptr;

int main(int argc, char** argv) {
    if (argc < 7) {
        std::cout << "FAIL";
        return 0;
    }
    const char* dbhost = argv[1];
    const char* dbuser = argv[2];
    const char* dbpass = argv[3];
    const char* dbname = argv[4];
    std::string user = argv[5];
    std::string pass = argv[6];

    DBConnection* db = DBConnection::getInstance();
    if (!db->connect(dbhost, dbuser, dbpass, dbname)) {
        std::cout << "FAIL";
        return 0;
    }

    MYSQL* conn = db->get();
    AuthManager* auth = AuthManager::getInstance();
    std::string res = auth->authenticate(conn, user, pass);
    std::cout << res;
    return 0;
}
