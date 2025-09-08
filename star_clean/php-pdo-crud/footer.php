</div>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkm" crossorigin="anonymous"></script>

<!-- jQuery (necessário para máscaras) -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<!-- jQuery Mask Plugin -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>

<script>
    $(document).ready(function() {
        // Máscara de telefone
        $('input[name="telefone"]').mask('(00) 00000-0000');

        // Máscara de CEP
        $('input[name="cep"]').mask('00000-000');

        // Máscara de CPF e CNPJ
        $('input[name="cpf_cnpj"]').on('input', function() {
            let value = $(this).val().replace(/\D/g, '');
            if (value.length <= 11) {
                $(this).mask('000.000.000-00');
            } else {
                $(this).mask('00.000.000/0000-00');
            }
        }).trigger('input');

        // Calendário automático para data de nascimento
        $('input[name="data_nascimento"]').attr('type', 'date');
    });

    // Função para preencher endereço via CEP (ViaCEP)
    function pesquisacep(valor) {
        var cep = valor.replace(/\D/g, '');
        if (cep != "") {
            var validacep = /^[0-9]{8}$/;
            if (validacep.test(cep)) {
                $('#logradouro').val("...");
                $('#bairro').val("...");
                $('#cidade').val("...");
                $('#uf').val("...");

                $.getJSON("https://viacep.com.br/ws/" + cep + "/json/?callback=?", function(dados) {
                    if (!("erro" in dados)) {
                        $("#logradouro").val(dados.logradouro);
                        $("#bairro").val(dados.bairro);
                        $("#cidade").val(dados.localidade);
                        $("#uf").val(dados.uf);
                    } else {
                        alert("CEP não encontrado.");
                        $('#logradouro,#bairro,#cidade,#uf').val('');
                    }
                });
            } else {
                alert("Formato de CEP inválido.");
                $('#logradouro,#bairro,#cidade,#uf').val('');
            }
        }
    }
</script>
</body>

</html>