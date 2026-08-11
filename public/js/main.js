function markNotificationAsRead(){
  $.get('/markAsRead');
}

function deleteNotification(){
  $.get('/deleteNotification');
}