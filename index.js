
function showToast(_message) {
    var myToastEl = document.querySelector('#toast');
    var myToast = bootstrap.Toast.getOrCreateInstance(myToastEl)
    document.querySelector('#toast_message').innerHTML = _message;
    myToast.show();
}
function checkin(dom) {
    const name = document.querySelector('#name').value;
    const temp = document.querySelector('#temp').value;

    if (!name || !temp) {
        alert("名前、体温を記入してください");
        return;
    }

    const icon = document.querySelector('#icon').value;
    const channel = "test";//document.querySelector('#channel').value;
    const message = document.querySelector('#message').value;

    if (37.5 <= temp) {
        alert("無理をせずお家で休みましょう");
        return;
    }

    const obj = {
        name: name,
        channel: channel,
        icon_emoji: icon,
        text: `*IN @ ${temp}* ${message}`
    };
    const method = "POST";
    const body = JSON.stringify(obj);
    const headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    };
    fetch("./slack.php", { method, headers, body })
        .then(response => response.json()) // 帰ってきた値をjsonにして次に渡す
        .then(res => {
            console.log(res);
            showToast(res.message);
        })
        .catch(console.error);

    dom.disabled = true;
    setTimeout(function () {
        dom.disabled = false;
    }, 1000);
}
function checkout(dom) {
    const name = document.querySelector('#name').value;
    const temp = document.querySelector('#temp').value;
    const icon = document.querySelector('#icon').value;
    const channel = "test";//document.querySelector('#channel').value;
    const message = document.querySelector('#message').value;
    const obj = {
        name: name,
        channel: channel,
        icon_emoji: icon,
        text: `*OUT* ${message}`
    };
    const method = "POST";
    const body = JSON.stringify(obj);
    const headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    };
    fetch("./slack.php", { method, headers, body })
        .then(response => response.json()) // 帰ってきた値をjsonにして次に渡す
        .then(res => {
            console.log(res);
        })
        .catch(console.error);

}

function saveText(obj) {
    localStorage.setItem(`labable_${obj.id}`, obj.value);
}
window.onload = function () {
    document.querySelector('#name').value = localStorage.getItem('labable_name');
    document.querySelector('#temp').value = localStorage.getItem('labable_temp');

    if (localStorage.getItem('labable_channel')) {
        document.querySelector('#channel').value = localStorage.getItem('labable_channel');
    }
    if (localStorage.getItem('labable_icon')) {
        document.querySelector('#icon').value = localStorage.getItem('labable_icon');
    }
    if (localStorage.getItem('labable_message')) {
        document.querySelector('#message').value = localStorage.getItem('labable_message');
    }

}