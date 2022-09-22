$.ajax({
    url: mail_url,
    type: 'POST',
    data: formData,
    contentType: false,
    processData: false,
    success:function(data){
        console.log(data)
        ym(89654120,'reachGoal','lead');
    },
    error:function(data){
        console.log(data)
    }
})