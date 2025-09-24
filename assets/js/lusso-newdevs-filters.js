// JS para manejar el submit AJAX del formulario de filtros y renderizar resultados sin recargar
jQuery(document).ready(function($){
    var $form = $('.lusso-filters');
    var $results = $('#lusso-search-results');
    if ($form.length === 0) return;

    function renderResults(data) {
        if (!$results.length) return;
        if (!data || !data.success || !data.data || !data.data.data) {
            $results.html('<div class="lusso-error">No results found.</div>');
            return;
        }
        var html = '';
        data.data.data.forEach(function(item){
            html += '<div class="lusso-result-item">';
            html += '<strong>' + (item.Title || item.Reference || 'Property') + '</strong><br>';
            html += (item.Location ? '<span>' + item.Location + '</span><br>' : '');
            html += (item.Price ? '<span>' + item.Price + '</span>' : '');
            html += '</div>';
        });
        // Paginación
        if (data.data.QueryId && data.data.PageNo < data.data.PageCount) {
            html += '<button class="lusso-load-more" data-queryid="'+data.data.QueryId+'" data-pageno="'+(data.data.PageNo+1)+'">'+LUSSO_NEWDEVS.loadMore+'</button>';
        }
        $results.html(html);
    }

    $form.on('submit', function(e){
        e.preventDefault();
        var formData = $form.serializeArray();
        formData.push({name:'nonce', value:LUSSO_NEWDEVS.nonce});
        formData.push({name:'lang', value:2}); // O ajusta según idioma
        formData.push({name:'page_size', value:20});
        $.post(LUSSO_NEWDEVS.ajaxUrl, formData, function(data){
            renderResults(data);
        });
    });

    $results.on('click', '.lusso-load-more', function(e){
        e.preventDefault();
        var queryId = $(this).data('queryid');
        var pageNo = $(this).data('pageno');
        var formData = $form.serializeArray();
        formData.push({name:'nonce', value:LUSSO_NEWDEVS.nonce});
        formData.push({name:'lang', value:2});
        formData.push({name:'query_id', value:queryId});
        formData.push({name:'page_no', value:pageNo});
        formData.push({name:'page_size', value:20});
        $.post(LUSSO_NEWDEVS.ajaxUrl, formData, function(data){
            renderResults(data);
        });
    });
});
