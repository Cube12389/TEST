

// 全局变量，用于存储当前用户的验证会话信息
let UserToken = '';
let UserEmail = '';

document.getElementById('tell').style.height = document.getElementById('reslogin').offsetHeight + 'px';

// 调整提示框高度函数
function ATH() {
    const loginElements = ['reslogin', 'reslogin-1', 'reslogin-erroy'];
    for (const elementId of loginElements) {
        const element = document.getElementById(elementId);
        if (element && element.style.display !== 'none') {
            document.getElementById('tell').style.height = element.offsetHeight + 'px';
            break;
        }
    }
}

function res() {
    document.getElementById('reslogin-erroy').style.display = 'none';
    document.getElementById('reslogin-1').style.display = 'none';
    document.getElementById('reslogin').style.display = 'block';
    UserToken = '';
    UserEmail = '';
    ATH();  
}

// 显示错误页面
function TellErroy(n) {
    document.getElementById('reslogin-erroy').style.height = document.getElementById('tell').offsetHeight + 'px';
    document.getElementById('erroy').innerHTML = n;
    document.getElementById('reslogin-erroy').style.display = 'block';
    document.getElementById('reslogin').style.display = 'none';
    document.getElementById('Res1').style.top = document.getElementById('tell').offsetHeight - 10 + 'px';
}

// 第一步骤：提交注册信息并获取验证码
function First() {
    const username = document.getElementById('username').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const password2 = document.getElementById('password2').value;
    
    // 前端验证
    if (username == "" || password == "" || password2 == "" || email == "") {
        TellErroy("这些信息不能为空");
        return;
    }
    
    if (password != password2) {
        TellErroy("两次密码不一致");
        return;
    }
    
    const usernameRegex = /^[a-zA-Z0-9]{2,15}$/;
    if (!usernameRegex.test(username)) {
        TellErroy("用户名格式不正确（2-15位字母数字）");
        return;
    }
    
    const passwordRegex = /^[a-zA-Z0-9%+\-=*]{6,15}$/;
    if (!passwordRegex.test(password)) {
        TellErroy("密码格式不正确（6-15位字母数字和特殊符号%+-=*）");
        return;
    }
    
    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    if (!emailRegex.test(email)) {
        TellErroy("邮箱格式不正确");
        return;
    }
    
    // 发送请求到后端
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            try {
                // 尝试解析JSON响应（验证码发送成功时）
                const response = JSON.parse(this.responseText);
                if (response.status === '200') {
                    // 保存token和邮箱
                    UserToken = response.token;
                    UserEmail = response.email;
                    
                    // 显示验证码输入页面
                    document.getElementById('reslogin').style.display = 'none';
                    document.getElementById('reslogin-1').style.display = 'block';
                    document.getElementById('EmailP').textContent = UserEmail;
                    ATH();
                }
            } catch (e) {
                // 如果不是JSON，处理错误码
                const errorCode = this.responseText;
                PE(errorCode);
            }
        }
    };
    
    xhttp.open("POST", "../php/reslogin_php.php", true);
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhttp.send("name=" + encodeURIComponent(username) + "&email=" + encodeURIComponent(email) + "&password=" + encodeURIComponent(password) + "&password2=" + encodeURIComponent(password2));
}

// 第二步骤：提交验证码完成注册
function Second() {
    const code = document.getElementById('EN').value;
    
    if (code == "") {
        TellErroy("请输入验证码");
        return;
    }
    
    if (UserToken == "") {
        TellErroy("验证会话已过期，请重新注册");
        return;
    }
    
    // 发送验证码到后端验证
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            const response = this.responseText;
            
            if (response == "201") {
                // 注册成功
                alert("注册成功！即将跳转到登录页面");
                window.location.href = "login.php";
            } else {
                PE(response);
            }
        }
    };
    
    xhttp.open("POST", "../php/reslogin_php.php", true);
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhttp.send("code=" + encodeURIComponent(code) + "&token=" + encodeURIComponent(UserToken));
}

// 处理错误码
function PE(code) {
    switch(code) {
        case "301":
            TellErroy("这些信息不能为空");
            break;
        case "302":
            TellErroy("两次密码不一致");
            break;
        case "303":
            TellErroy("用户名格式不正确");
            break;
        case "304":
            TellErroy("密码格式不正确");
            break;
        case "305":
            TellErroy("邮箱格式不正确");
            break;
        case "400":
            TellErroy("用户名已被注册");
            break;
        case "401":
            TellErroy("邮箱已被注册");
            break;
        case "402":
            TellErroy("验证会话已过期，请重新注册");
            break;
        case "403":
            TellErroy("无效的验证会话，请重新注册");
            break;
        case "404":
            TellErroy("验证码已过期，请重新注册");
            break;
        case "405":
            TellErroy("验证码错误，请重新输入");
            break;
        case "500":
            TellErroy("服务器错误，请稍后重试");
            break;
        case "501":
            TellErroy("发送验证码失败，请稍后重试");
            break;
        default:
            TellErroy("未知错误，请稍后重试");
    }
}