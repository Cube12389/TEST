

function res() {
    var logindiv = document.getElementById('login');
    logindiv.innerHTML = "<h3>登录</h3><p>用户名</p><input type=\"text\" id=\"username\" placeholder=\"请输入用户名\"><p>密码</p><input type=\"password\" id=\"password\" placeholder=\"请输入密码\"><br><br><a href=\"reslogin.php\">注册账号</a><button onclick=\"login();\">登录</button>";
}

function login() {
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    var logindiv = document.getElementById('login');
    if (username == "" || password == "") {
        logindiv.innerHTML = "<h3 class='text'>请输入用户名和密码</h3><button style='top: 330px;' onclick='res();'>返回登录</button>";
        return;
    } const usernameRegex = /^[a-zA-Z0-9]{2,15}$/;
    if (!usernameRegex.test(username)) {
        logindiv.innerHTML = "<h3 class='text'>用户名格式不正确</h3><p>用户名长度应为2-15位，仅包含字母和数<br>字</p><button style='top: 270px;' onclick='res();'>返回登录</button>";
        return;
    } const passwordRegex = /^[a-zA-Z0-9%+\-=*]{6,15}$/;
    if (!passwordRegex.test(password)) {
        logindiv.innerHTML = "<h3 class='text'>密码格式不正确</h3><p>密码长度应为6-15位，包含字母、数字<br>和特殊字符%+-=*</p><button style='top: 270px;' onclick='res();'>返回登录</button>";
        return;
    } var xhttp;
    xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            const i = this.responseText;
            if (i == 404) {
                logindiv.innerHTML = "<h3 class='text'>没有此用户</h3><button style='top: 330px;' onclick='res();'>返回登录</button>";
            } else if (i == 403) {
                logindiv.innerHTML = "<h3 class='text'>密码错误</h3><button style='top: 330px;' onclick='res();'>返回登录</button>";
            } else if (i == 200) {
                logindiv.innerHTML = "<h3 class='text'>登录成功</h3><button style='top: 330px;' onclick=\"window.location.href = \'index.php\';\">返回首页</button>";
            } else if (i == 405) {
                logindiv.innerHTML = "<h5 class='text'>不是bor<br>你知道吗，出现这个错误的概率约为10的-553次方<br>相当于 “从宇宙中随机挑选一个原子，连续 10 次都挑中同一个” 的概率还要低亿万倍</h5><button style='top: 330px;' onclick='res();'>返回登录</button>";
            }
        }
    };
    xhttp.open("POST", "../php/login_php.php", true);
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhttp.send("name=" + username + "&password=" + password);
}