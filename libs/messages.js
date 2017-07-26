function number_format(number, decimals, dec_point, thousands_sep) {
  
  number = (number + '')
    .replace(/[^0-9+\-Ee.]/g, '');
  var n = !isFinite(+number) ? 0 : +number,
    prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
    sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
    dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
    s = '',
    toFixedFix = function(n, prec) {
      var k = Math.pow(10, prec);
      return '' + (Math.round(n * k) / k)
        .toFixed(prec);
    };
  // Fix for IE parseFloat(0.55).toFixed(0) = 0;
  s = (prec ? toFixedFix(n, prec) : '' + Math.round(n))
    .split('.');
  if (s[0].length > 3) {
    s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
  }
  if ((s[1] || '')
    .length < prec) {
    s[1] = s[1] || '';
    s[1] += new Array(prec - s[1].length + 1)
      .join('0');
  }
  return s.join(dec);
}

exports.message = {
	transfer_negotiation_success:function(club_name,player_name,offer_price){
		return "Selamat, "+club_name+" telah menyetujui penawaran anda terhadap "+player_name+" sejumlah ss$"+number_format(offer_price)+"!";
	},
  insufficient_budget:function(club_name,player_name,offer_price){
    return "Mohon maaf, penawaran anda untuk meminang "+player_name+" dari "+club_name+" tidak dapat dilakukan mengingat budget klub tidak mencukupi !";
  },
	transfer_negotiation_failed:function(club_name,player_name,offer_price){
		return club_name+" menolak penawaran anda terhadap "+player_name+". ss$"+number_format(offer_price)+" masih terlalu kecil untuk melepas "+player_name+" ke klub anda.";
	},
  salary_nego_success:function(player_name,offer_price){
    return "Selamat, "+player_name+" telah menyetujui kontrak senilai ss$"+number_format(offer_price)+"! Setelah proses administrasi selesai, "+player_name+" akan segera bergabung ke dalam klub !";
  },
  salary_nego_failed:function(player_name,offer_price){
    return "Halo\n, "+player_name+" Menolak penawaran kontrak anda !";
  },
  salary_nego_failed2:function(player_name,offer_price){
    return "Halo\n, "+player_name+" Menerima penawaran kontrak anda ! Namun klub tidak dapat membeli tambahan pemain baru lagi pada transfer window ini.";
  },
};