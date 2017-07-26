<div class="foo"></div>
<script type="text/template" id="tpl">
  <% for(var i in data){ %>
    <div><%=data[i].name%></div>

  <% } %>
</script>

<script>
  var View
  var Model = Backbone.Model.extend({});
  var model = new Model();
  model.on('change:data',function(model,data){
    $(".foo").html('');
    render_view('#tpl','.foo',{data:data});
  });
  var url = 'http://stats.supersoccer.co.id/stats/report/8?game_id=f694942&team_id=t8';
   $.getJSON(url + "&callback=?", null, function(response) {
        console.log(response)
    });
 
  var data = [
    {name:'foo'},
    {name:'bar'}
  ];
  
  model.set({data:data});
</script>