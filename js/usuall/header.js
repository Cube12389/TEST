
// 导航栏滚动
var SY = window.pageYOffset;
window.onscroll = function() {
  var cnt = window.pageYOffset;
  if (SY > cnt) 
    document.getElementById("up").style.top = "0";
  else 
    document.getElementById("up").style.top = "-50px";
  SY = cnt;
}

// 移动端下拉菜单
function ShowMenu() {
  var menuContent = document.getElementById("menu-content");
  if (getComputedStyle(menuContent).display === "block") {
    menuContent.style.display = "none";
  } else {
    menuContent.style.display = "block";
  }
}

window.onclick = function(event) {
  if (!event.target.matches('.menu') && !event.target.closest('.menu-content')) {
    document.getElementById("menu-content").style.display = "none";
  }
}