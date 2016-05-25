
function parseHttp(text, header) {
    var obj = JSON.parse(text);
    document.getElementById("header").innerHTML = obj; //.employees[1].firstName + " " + obj.employees[1].lastName;

}