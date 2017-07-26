<div id="chart">
</div>

<script>
var data = <?=json_encode($rs)?>;
var seconds = [];
var category = [];
var minutes = [];
for(var i in data){
	category.push(data[i].dt);
	seconds.push(data[i].d);
	minutes.push(data[i].m);
}
$(function () {
    $('#chart').highcharts({
        chart: {
            type: 'line'
        },
        title: {
            text: 'Opta Push Performance'
        },
        
        xAxis: {
            categories: category,
            labels:{
            	rotation:60
            }
        },
        yAxis: {
            title: {
                text: 'Time'
            }
        },
        tooltip: {
            enabled: false,
            formatter: function() {
                return '<b>'+ this.series.name +' : '+this.y +'';
            }
        },
        plotOptions: {
            line: {
                dataLabels: {
                    enabled: true
                },
                enableMouseTracking: false
            }
        },
        series: [{
            name: 'in Seconds',
            data: seconds
        }, {
            name: 'in Minutes',
            data: minutes
        }]
    });
});
</script>


