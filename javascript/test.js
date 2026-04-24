/* ------------------------------------------------------------------ */
/* Tests                                                               */
/* ------------------------------------------------------------------ */

var _testLogueado = sessionStorage.getItem('pf_session') !== null;
function _pfUserId() {
    try { return JSON.parse(sessionStorage.getItem('pf_session')).idUsuario; } catch(e) { return 'guest'; }
}


/* elegirEspecie — llamada desde los botones de icono del HTML.
   Guarda el filtro y arranca el test saltándose la primera pregunta
   (especie) que ahora es redundante */
function elegirEspecie(especie) {
    estadoTest.compatibilidad.especieFiltro = especie;
    /* Ocultar selección de especie y mostrar barra de progreso + preguntas */
    document.getElementById('compat-seleccion-especie').classList.add('d-none');
    document.getElementById('prog-wrap-compatibilidad').classList.remove('d-none');
    document.getElementById('preguntas-compatibilidad').classList.remove('d-none');
    document.getElementById('btn-siguiente-compatibilidad').classList.remove('d-none');
    /* Saltar la pregunta 0 (especie) — empezar desde la 1 */
    estadoTest.compatibilidad.preguntaActual = 1;
    renderPregunta('compatibilidad');
}

// ----------------------------------------------------------------
// test conocimientos

var todasLasPreguntas = [
    {
        pregunta: "Un perro que come hierba de forma ocasional probablemente está...",
        opciones: ["Buscando minerales que le faltan en su dieta", "Con náuseas o molestias digestivas", "Aburrido", "Todas las anteriores pueden ser la causa"],
        correcta: 3,
        explicacion: "Comer hierba puede deberse a náuseas, falta de fibra, aburrimiento o simplemente instinto. No siempre indica un problema."
    },
    {
        pregunta: "¿Qué significa que un gato te muestre la barriga pero gruña si intentas tocarla?",
        opciones: ["Quiere que le rasques el vientre", "Es una señal de confianza pero no una invitación a tocarlo", "Tiene dolor abdominal", "Está jugando"],
        correcta: 1,
        explicacion: "Mostrar la barriga es señal de confianza, pero para muchos gatos es una zona hipersensible. No es una invitación a tocarla."
    },
    {
        pregunta: "¿Cuál de estos alimentos puede causar anemia hemolítica en perros y gatos?",
        opciones: ["Zanahoria cruda", "Cebolla y ajo", "Arroz blanco cocido", "Pechuga de pollo hervida"],
        correcta: 1,
        explicacion: "La cebolla y el ajo contienen tiosulfatos que dañan los glóbulos rojos tanto en perros como en gatos, incluso cocinados o en polvo."
    },
    {
        pregunta: "La enfermedad periodontal en perros comienza a ser frecuente a partir de...",
        opciones: ["Los 6 meses", "El primer año de vida", "Los 3 años", "Solo en perros ancianos"],
        correcta: 2,
        explicacion: "Estudios veterinarios indican que más del 80% de los perros mayores de 3 años presentan algún grado de enfermedad periodontal."
    },
    {
        pregunta: "¿Qué diferencia a un gato europeo de un gato común o mestizo?",
        opciones: ["El europeo es una raza reconocida con estándar oficial", "Son exactamente lo mismo", "El europeo tiene pedigrí obligatorio", "Solo se diferencia por el color del pelaje"],
        correcta: 0,
        explicacion: "El Europeo es una raza reconocida por la FIFe con estándar definido, aunque físicamente se parece mucho al gato doméstico común."
    },
    {
        pregunta: "¿Para qué sirve el microchip en un perro?",
        opciones: ["Para rastrear su ubicación en tiempo real", "Como identificación permanente vinculada a una base de datos", "Para monitorizar su salud", "Para detectar enfermedades hereditarias"],
        correcta: 1,
        explicacion: "El microchip es un identificador único, no un GPS. Solo sirve para identificar al animal cuando se escanea con un lector específico."
    },
    {
        pregunta: "¿Cuál es la temperatura corporal normal de un perro adulto?",
        opciones: ["35-36,5 °C", "36,5-37,5 °C", "38-39,2 °C", "39,5-41 °C"],
        correcta: 2,
        explicacion: "La temperatura normal de un perro adulto oscila entre 38 y 39,2 °C. Por encima de 39,5 °C se considera fiebre."
    },
    {
        pregunta: "Un gato que vocaliza mucho por la noche sin razón aparente puede estar indicando...",
        opciones: ["Que tiene hambre", "Hipertiroidismo, dolor o deterioro cognitivo", "Que quiere salir", "Solo busca atención"],
        correcta: 1,
        explicacion: "La vocalización nocturna excesiva en gatos mayores puede ser síntoma de hipertiroidismo, hipertensión, dolor crónico o síndrome cognitivo felino."
    },
    {
        pregunta: "¿Cuántas vértebras tiene la cola de un gato en condiciones normales?",
        opciones: ["Entre 5 y 10", "Entre 18 y 23", "Exactamente 30", "Depende de la raza"],
        correcta: 1,
        explicacion: "La cola de un gato tiene entre 18 y 23 vértebras caudales, lo que le otorga su extraordinaria flexibilidad y equilibrio."
    },
    {
        pregunta: "¿Qué es la toxoplasmosis y cómo se transmite principalmente a humanos?",
        opciones: ["Una bacteria transmitida por la saliva del gato", "Un parásito cuyas ooquistes se encuentran en las heces del gato", "Un hongo que vive en el pelo del gato", "Un virus transmitido por arañazos"],
        correcta: 1,
        explicacion: "La toxoplasmosis la causa Toxoplasma gondii. Los humanos se infectan principalmente por ingerir carne cruda o por contacto con heces de gato infectado, no por acariciar al animal."
    },
    {
        pregunta: "¿Cuál es la función principal de los bigotes de un gato?",
        opciones: ["Detectar cambios en la temperatura", "Medir espacios y detectar cambios en el aire y el entorno", "Regular el equilibrio", "Comunicar emociones exclusivamente"],
        correcta: 1,
        explicacion: "Los bigotes son órganos sensoriales que detectan cambios en el viento, miden espacios y perciben vibraciones. También comunican estado emocional."
    },
    {
        pregunta: "Un perro que lame compulsivamente sus patas delanteras probablemente tiene...",
        opciones: ["Aburrimiento o ansiedad, posible alergia o dermatitis", "Hambre", "Un tic nervioso sin importancia", "Deficiencia de vitamina B"],
        correcta: 0,
        explicacion: "El lamido compulsivo de patas suele indicar alergia ambiental, alimentaria, dermatitis atópica o ansiedad. Requiere evaluación veterinaria."
    },
    {
        pregunta: "¿Qué es el síndrome de Horner en perros y gatos?",
        opciones: ["Una infección ocular bacteriana", "Un trastorno neurológico que afecta a los músculos del ojo y el párpado", "Una alergia a ácaros del polvo", "Una enfermedad hereditaria de la retina"],
        correcta: 1,
        explicacion: "El síndrome de Horner afecta el nervio simpático del ojo, causando ptosis, miosis y enoftalmos. Puede indicar lesiones neurológicas o del oído medio."
    },
    {
        pregunta: "¿En qué se diferencia la panleucopenia felina del parvovirus canino?",
        opciones: ["Son enfermedades totalmente distintas sin relación", "Son causadas por virus del mismo grupo pero afectan a especies distintas", "La panleucopenia es bacteriana y el parvo viral", "El parvo afecta solo a cachorros y la panleucopenia a adultos"],
        correcta: 1,
        explicacion: "Ambas son causadas por parvovirus altamente emparentados. La panleucopenia afecta a felinos y el parvovirus tipo 2 a cánidos, con síntomas similares."
    },
    {
        pregunta: "¿Cuántas horas duerme de media un gato adulto al día?",
        opciones: ["8-10 horas", "10-12 horas", "12-16 horas", "Más de 18 horas siempre"],
        correcta: 2,
        explicacion: "Los gatos duermen entre 12 y 16 horas al día de media. Son animales de actividad crepuscular que conservan energía para cazar."
    },
    {
        pregunta: "¿Qué indica el color amarillo-verdoso en el moco nasal de un perro?",
        opciones: ["Deshidratación leve", "Posible infección bacteriana o vírica que requiere atención veterinaria", "Es un color normal en razas braquicéfalas", "Deficiencia de zinc"],
        correcta: 1,
        explicacion: "El moco nasal amarillo-verdoso puede indicar rinitis bacteriana, distemper u otras infecciones. Siempre debe consultarse al veterinario."
    },
    {
        pregunta: "La leishmaniosis canina en España se transmite por...",
        opciones: ["Contacto directo con un perro infectado", "La picadura del flebótomo (mosca de la arena)", "Agua contaminada", "Garrapatas"],
        correcta: 1,
        explicacion: "Leishmania infantum se transmite exclusivamente a través de la picadura de flebótomos infectados, especialmente activos al anochecer en meses cálidos."
    },
    {
        pregunta: "¿Qué ocurre fisiológicamente cuando un perro jadea?",
        opciones: ["Elimina CO2 acumulado", "Regula su temperatura corporal mediante la evaporación en lengua y vías respiratorias", "Está teniendo un ataque de ansiedad", "Compensa la falta de glóbulos rojos"],
        correcta: 1,
        explicacion: "Los perros tienen muy pocas glándulas sudoríparas. El jadeo es su mecanismo principal de termorregulación mediante la evaporación de humedad."
    },
    {
        pregunta: "¿Cuál es la edad mínima recomendada para separar a un cachorro de su madre?",
        opciones: ["4 semanas", "6 semanas", "8 semanas", "12 semanas siempre"],
        correcta: 2,
        explicacion: "Las 8 semanas son el mínimo recomendado. El período entre 6 y 12 semanas es crítico para la socialización. Separar antes aumenta el riesgo de problemas de conducta."
    },
    {
        pregunta: "¿Qué significa que una gata esté en celo pero no se haya apareado?",
        opciones: ["Entrará en pseudogestación automáticamente", "Puede entrar en ciclo de celo continuo que agota hormonalmente", "No tiene consecuencias para su salud", "Se convertirá en gata territorial"],
        correcta: 1,
        explicacion: "Las gatas son de ovulación inducida. Sin cópula pueden encadenarse ciclos de celo continuos que elevan el riesgo de infecciones uterinas y tumores mamarios."
    },
    {
        pregunta: "¿Qué es la displasia de cadera en perros?",
        opciones: ["Una luxación de rótula", "Un desarrollo anormal de la articulación coxofemoral", "Una fractura por estrés", "Una enfermedad muscular autoinmune"],
        correcta: 1,
        explicacion: "La displasia de cadera es un desarrollo anormal de la articulación coxofemoral que causa laxitud, dolor y artritis progresiva. Es muy prevalente en razas grandes."
    },
    {
        pregunta: "¿A qué edad se recomienda realizar la primera radiografía para descartar displasia en razas predispuestas?",
        opciones: ["3 meses", "6 meses", "12-18 meses cuando los huesos están formados", "Solo si hay síntomas"],
        correcta: 2,
        explicacion: "La valoración radiológica oficial de displasia se hace con la cadera madura, entre 12 y 24 meses según el organismo de referencia (OFA, BVA, FCI)."
    },
    {
        pregunta: "Un gato que rechina los dientes puede estar indicando...",
        opciones: ["Emoción intensa", "Dolor, especialmente dental o abdominal", "Que necesita más calcio", "Es un comportamiento normal en gatos mayores"],
        correcta: 1,
        explicacion: "El rechinar de dientes en gatos, llamado bruxismo, suele asociarse a dolor dental, nauseas o dolor abdominal. Requiere revisión veterinaria."
    },
    {
        pregunta: "¿Qué es la filariosis o dirofilariosis canina?",
        opciones: ["Una infestación de pulgas específica del corazón", "Una parasitosis causada por nematodos que colonizan el corazón y pulmones", "Una infección bacteriana del sistema circulatorio", "Una enfermedad genética cardíaca"],
        correcta: 1,
        explicacion: "El gusano del corazón, Dirofilaria immitis, se transmite por mosquitos y puede causar insuficiencia cardíaca y pulmonar grave si no se previene."
    },
    {
        pregunta: "¿Cuál de estas razas caninas tiene predisposición genética a la atrofia progresiva de retina?",
        opciones: ["Bulldog Francés", "Labrador Retriever", "Chihuahua", "Doberman"],
        correcta: 1,
        explicacion: "El Labrador Retriever, junto con el Golden y el Cocker Spaniel, tiene alta prevalencia de atrofia progresiva de retina, una enfermedad hereditaria degenerativa."
    },
    {
        pregunta: "¿Qué es la piometra y qué la hace especialmente peligrosa?",
        opciones: ["Una infección del oído que puede extenderse al cerebro", "Una infección uterina que puede ser asintomática hasta ser mortal", "Una inflamación de la glándula mamaria", "Un tumor ovárico benigno"],
        correcta: 1,
        explicacion: "La piometra es una acumulación de pus en el útero. La forma cerrada puede no dar síntomas externos hasta que el útero se rompe, con riesgo de muerte."
    },
    {
        pregunta: "¿Por qué no se recomienda dar leche de vaca a gatos adultos?",
        opciones: ["Porque es tóxica para ellos", "Porque la mayoría son intolerantes a la lactosa", "Porque engorda demasiado", "Porque altera el pH urinario"],
        correcta: 1,
        explicacion: "La mayoría de los gatos adultos pierden la lactasa, enzima que digiere la lactosa. La leche de vaca puede causarles diarrea y molestias digestivas."
    },
    {
        pregunta: "Un perro con abdomen distendido y que intenta vomitar sin éxito puede estar sufriendo...",
        opciones: ["Indigestión leve", "Torsión gástrica, una emergencia veterinaria grave", "Estreñimiento severo", "Acumulación de gases sin importancia"],
        correcta: 1,
        explicacion: "La torsión gástrica (GDV) es una emergencia mortal en pocas horas. Es más frecuente en razas grandes de pecho profundo como el Gran Danés o el Pastor Alemán."
    },
    {
        pregunta: "¿Qué vacuna del calendario felino protege también contra el virus de la leucemia felina?",
        opciones: ["La triple felina estándar", "La vacuna específica FeLV, que es adicional y opcional", "La antirrábica", "No existe vacuna contra la leucemia felina"],
        correcta: 1,
        explicacion: "La leucemia felina (FeLV) tiene vacuna propia, separada de la triple o cuádruple estándar. Se recomienda especialmente en gatos con acceso al exterior."
    },
    {
        pregunta: "¿Cuándo es más preciso un test de embarazo veterinario por ecografía en perras?",
        opciones: ["A los 5-7 días de la monta", "Entre los 20 y 30 días de gestación", "Solo en la última semana", "La ecografía no detecta embarazo en perras"],
        correcta: 1,
        explicacion: "Los sacos gestacionales son visibles por ecografía a partir del día 20-25. Antes puede haber falsos negativos. La gestación canina dura unos 63 días."
    },
    {
        pregunta: "¿Qué es el síndrome braquicefálico y qué razas lo padecen?",
        opciones: ["Una malformación de columna en razas largas como el Dachshund", "Un conjunto de problemas respiratorios en razas de cara aplastada como Bulldog o Pug", "Una enfermedad cardíaca hereditaria del Cavalier", "Un problema articular del Shar Pei"],
        correcta: 1,
        explicacion: "El síndrome braquicefálico incluye narinas estenóticas, paladar blando alargado y tráquea hipoplásica. Afecta a Bulldogs, Pugs, Bostons y otras razas con morro corto."
    },
    {
        pregunta: "¿Qué indica el fenómeno de Raynaud si se observa en las almohadillas de un gato?",
        opciones: ["Hipervitaminosis A", "Es extremadamente raro; los cambios de color en almohadillas suelen indicar problemas circulatorios o anemia", "Alergia de contacto", "Infección fúngica"],
        correcta: 1,
        explicacion: "Los cambios de coloración en almohadillas pueden indicar anemia, problemas circulatorios o hipotermia. Siempre requieren evaluación veterinaria."
    },
    {
        pregunta: "¿Qué diferencia a la rabia de otras encefalitis virales en perros?",
        opciones: ["Que la rabia no afecta al sistema nervioso central", "Que es siempre mortal una vez que aparecen los síntomas neurológicos y es zoonótica", "Que solo se transmite por mordedura entre perros", "Que existe tratamiento efectivo si se detecta a tiempo"],
        correcta: 1,
        explicacion: "La rabia es mortal al 100% una vez aparecen síntomas. Es zoonótica y de declaración obligatoria. No existe tratamiento postclinical efectivo para animales."
    },
    {
        pregunta: "Un gato que de repente deja de usar el arenero puede estar indicando...",
        opciones: ["Que le disgusta el tipo de arena", "Infección urinaria, cistitis intersticial felina o dolor al orinar", "Que prefiere el exterior", "Comportamiento normal en gatos mayores de 10 años"],
        correcta: 1,
        explicacion: "El cambio brusco en los hábitos de eliminación es uno de los síntomas más claros de FLUTD (enfermedad del tracto urinario inferior felino). Requiere consulta urgente."
    },
    {
        pregunta: "¿Qué es la cardiomiopatía hipertrófica felina (HCM)?",
        opciones: ["Una inflamación del pericardio por infección", "El engrosamiento anormal de la pared del ventrículo izquierdo, la cardiopatía más frecuente en gatos", "Una malformación congénita del septo interventricular", "Una consecuencia de la hipertensión secundaria a la obesidad"],
        correcta: 1,
        explicacion: "La HCM es la cardiopatía más prevalente en gatos. El Maine Coon y el Ragdoll tienen mutaciones genéticas identificadas. Puede ser asintomática hasta causar tromboembolismo."
    },
    {
        pregunta: "¿Por qué se recomienda no cortar el pelo de un Husky Siberiano en verano?",
        opciones: ["Por tradición cultural de la raza", "Su doble capa actúa como aislante térmico en ambos sentidos: frío y calor", "Porque el pelo no vuelve a crecer igual", "Porque puede desarrollar fobia al calor"],
        correcta: 1,
        explicacion: "La doble capa del Husky regula la temperatura. La capa interior actúa de barrera contra el calor. Raparla elimina esta protección y puede dañar el ciclo de crecimiento."
    },
    {
        pregunta: "¿Cuál de estos parásitos internos puede transmitirse a humanos a través de la tierra contaminada por heces de perro?",
        opciones: ["Dipylidium caninum (tenia del perro)", "Toxocara canis (larva migrans)", "Giardia lamblia exclusivamente", "Ninguno, los parásitos de perros no afectan a humanos"],
        correcta: 1,
        explicacion: "Los huevos de Toxocara canis son infecciosos en el suelo. En humanos causan larva migrans visceral o ocular, especialmente peligrosa en niños."
    },
    {
        pregunta: "¿Qué indica la frecuencia cardíaca en reposo de un perro adulto grande (más de 25 kg)?",
        opciones: ["60-100 ppm es normal", "60-160 ppm es el rango normal", "Siempre debe estar por encima de 100 ppm", "La talla no influye en la frecuencia cardíaca normal"],
        correcta: 0,
        explicacion: "En perros grandes, la frecuencia cardíaca normal en reposo es de 60-100 ppm. En razas pequeñas puede llegar a 140-160 ppm. La talla sí influye."
    },
    {
        pregunta: "Un gato que maúlla justo después de usar el arenero puede estar indicando...",
        opciones: ["Satisfacción después de defecar", "Dolor o dificultad para orinar o defecar", "Que quiere que le limpien el arenero inmediatamente", "Comportamiento de marcaje"],
        correcta: 1,
        explicacion: "Vocalizar después o durante el uso del arenero suele indicar dolor, estreñimiento, obstrucción urinaria o inflamación. Es una señal que no debe ignorarse."
    },
    {
        pregunta: "¿Qué es la pancreatitis en perros y qué la suele desencadenar?",
        opciones: ["Una inflamación del páncreas frecuentemente desencadenada por comidas muy grasas", "Una infección bacteriana del intestino delgado", "Una enfermedad autoinmune de la sangre", "Un problema renal que afecta secundariamente al páncreas"],
        correcta: 0,
        explicacion: "La pancreatitis canina es frecuente tras la ingesta de alimentos grasos. Causa dolor abdominal severo, vómitos y puede ser mortal en su forma aguda grave."
    },
    {
        pregunta: "¿Cuál de estas razas de gato tiene predisposición genética a la poliquistosis renal (PKD)?",
        opciones: ["Siamés", "Bengalí", "Persa y sus derivados", "Maine Coon"],
        correcta: 2,
        explicacion: "La PKD afecta principalmente a razas derivadas del Persa. Es autosómica dominante, detectable por test genético. El Maine Coon tiene HCM, no PKD."
    },
    {
        pregunta: "¿Qué ocurre en el organismo de un gato si se le da acetaminofén (paracetamol)?",
        opciones: ["No tiene efectos secundarios en dosis bajas", "Es tóxico porque los gatos carecen de la enzima para metabolizarlo correctamente", "Actúa igual que en humanos pero a dosis más bajas", "Solo es peligroso en cachorros"],
        correcta: 1,
        explicacion: "Los gatos carecen de glucuronil transferasa para metabolizar el paracetamol. Causa metahemoglobinemia, daño hepático y muerte. Incluso un comprimido puede ser letal."
    },
    {
        pregunta: "¿Qué es el comportamiento de alorrubado en gatos?",
        opciones: ["Marcar territorio orinando sobre objetos verticales", "El frotamiento mutuo entre gatos como comportamiento social de vínculo", "Rascar superficies para afilar las uñas", "Emitir sonidos guturales durante la caza"],
        correcta: 1,
        explicacion: "El alorrubado es el frotamiento de las glándulas faciales entre individuos. Refuerza el vínculo social y crea una 'firma olfativa' grupal. Solo ocurre entre animales que se aceptan."
    },
    {
        pregunta: "¿Cuántos dientes permanentes tiene un perro adulto?",
        opciones: ["28", "38", "42", "44"],
        correcta: 2,
        explicacion: "El perro adulto tiene 42 dientes permanentes: 12 incisivos, 4 caninos, 16 premolares y 10 molares. Los cachorros tienen 28 dientes de leche."
    },
    {
        pregunta: "La enfermedad de Addison en perros afecta principalmente a...",
        opciones: ["Las glándulas tiroides", "Las glándulas suprarrenales, causando déficit de corticoides y mineralocorticoides", "El páncreas exocrino", "La glándula pituitaria"],
        correcta: 1,
        explicacion: "La enfermedad de Addison es una insuficiencia adrenocortical. Se manifiesta con letargia, vómitos y en crisis puede causar colapso cardiovascular (crisis addisoniana)."
    },
    {
        pregunta: "¿Qué diferencia hay entre un gato castrado y un gato esterilizado?",
        opciones: ["Son términos sinónimos", "Castrado implica extirpación de gónadas; esterilizado puede incluir técnicas que conservan las gónadas pero impiden la reproducción", "Esterilizado solo aplica a hembras", "Castrado se refiere solo a machos y esterilizado a hembras"],
        correcta: 1,
        explicacion: "Castración es la gonadectomía (extirpación). Esterilización es el término genérico que incluye también ligadura de trompas o vasectomía, donde se conservan las gónadas."
    },
    {
        pregunta: "¿Qué es la iridociclitis en un gato y qué la puede causar?",
        opciones: ["Una infección del oído interno de origen viral", "Una inflamación de la úvea ocular, asociada frecuentemente a FIV, FeLV o Toxoplasma", "Una lesión corneal superficial por arañazo", "Una enfermedad autoinmune de la piel alrededor de los ojos"],
        correcta: 1,
        explicacion: "La uveítis felina puede ser idiopática o asociada a enfermedades sistémicas como FIV, FeLV, Bartonella o Toxoplasma. Requiere tratamiento urgente para evitar ceguera."
    },
    {
        pregunta: "¿Cuál es la función de la vacuna contra el leptospira en perros?",
        opciones: ["Prevenir una enfermedad bacteriana transmitida por el agua y roedores que afecta riñones e hígado", "Prevenir una infección respiratoria de origen viral", "Proteger contra parásitos intestinales", "Reforzar la inmunidad general del animal"],
        correcta: 0,
        explicacion: "La leptospirosis es causada por Leptospira spp., bacteria presente en agua contaminada por roedores. Afecta riñones e hígado y es zoonótica. La vacuna es parte del protocolo básico."
    },
    {
        pregunta: "¿Qué indica un ritmo respiratorio de 40 respiraciones por minuto en un gato en reposo?",
        opciones: ["Es completamente normal", "Está ligeramente elevado pero no es urgente", "Es taquipnea y puede indicar patología cardíaca o respiratoria grave", "Solo es preocupante en gatos menores de 1 año"],
        correcta: 2,
        explicacion: "La frecuencia respiratoria normal en reposo de un gato es 16-30 rpm. Por encima de 30 en reposo es taquipnea y puede indicar efusión pleural, asma o cardiomiopatía."
    },
    {
        pregunta: "¿Qué es la FLUTD y cuál es uno de sus factores de riesgo más documentados?",
        opciones: ["Una enfermedad genética del hígado; el factor de riesgo es la consanguinidad", "Un conjunto de trastornos del tracto urinario inferior felino; el estrés es un factor de riesgo relevante", "Una infección bacteriana exclusiva de gatas; el factor de riesgo es la promiscuidad", "Una parasitosis vesical; el agua estancada es el principal factor"],
        correcta: 1,
        explicacion: "La FLUTD agrupa cistitis idiopática, urolitiasis y taponamiento uretral. El estrés crónico, la dieta seca y la obesidad son factores de riesgo bien documentados."
    },
    {
        pregunta: "¿Qué es la alopecia X en perros nórdicos como el Pomerania?",
        opciones: ["Una deficiencia de vitamina A que provoca caída del pelo", "Una alopecia no inflamatoria de causa hormonal no del todo aclarada, asociada a razas de pelaje nórdico", "Una infestación parasitaria específica de razas árticas", "Una reacción autoinmune que destruye los folículos pilosos"],
        correcta: 1,
        explicacion: "La alopecia X afecta especialmente a Pomerania, Chow Chow y Spitz. No produce picor ni inflamación. Su causa exacta no está completamente aclarada aunque se vincula a andrógenos."
    },
    {
        pregunta: "¿Qué diferencia a la giardiasis de otras parasitosis intestinales en perros?",
        opciones: ["Que no produce ningún síntoma nunca", "Que es un protozoo que afecta a la absorción de nutrientes y puede causar diarrea crónica en perros jóvenes", "Que solo se transmite entre animales de la misma camada", "Que es invisible para los análisis coprológicos convencionales"],
        correcta: 1,
        explicacion: "Giardia es un protozoo flagelado que causa diarrea intermitente y mala absorción. Su detección requiere métodos específicos (flotación con sulfato de zinc o antígeno fecal)."
    },
    {
        pregunta: "Un perro que gira en círculos y pierde el equilibrio repentinamente puede estar sufriendo...",
        opciones: ["Un golpe de calor moderado", "Síndrome vestibular (periférico o central)", "Hipoglucemia leve", "Falta de ejercicio acumulada"],
        correcta: 1,
        explicacion: "El síndrome vestibular provoca nistagmo, inclinación de la cabeza, ataxia y giro en círculos. La forma periférica en ancianos puede parecer un ictus pero a menudo se resuelve sola."
    },
    {
        pregunta: "¿Por qué el xilitol es especialmente peligroso para los perros?",
        opciones: ["Porque es un compuesto estrogénico que altera su comportamiento", "Porque provoca una liberación masiva de insulina y puede causar hipoglucemia grave y daño hepático", "Porque bloquea la absorción de calcio", "Porque es irritante gástrico que puede perforar la mucosa"],
        correcta: 1,
        explicacion: "El xilitol, edulcorante presente en chicles y productos sin azúcar, estimula la liberación de insulina en perros de forma masiva, causando hipoglucemia y potencialmente necrosis hepática."
    },
    {
        pregunta: "¿Cuántas razas de gato reconoce actualmente la FIFe (Federación Internacional Felina)?",
        opciones: ["Menos de 20", "Entre 43 y 50", "Más de 100", "Exactamente 30"],
        correcta: 1,
        explicacion: "La FIFe reconoce alrededor de 43-50 razas dependiendo del momento de actualización. La TICA, más inclusiva, reconoce más de 70 razas."
    },
    {
        pregunta: "¿Qué es la condroitina y por qué se suplementa en perros con artrosis?",
        opciones: ["Es una vitamina liposoluble que lubrica las articulaciones", "Es un glicosaminoglicano que forma parte del cartílago y cuya suplementación puede ralentizar su degradación", "Es un antiinflamatorio de origen natural equivalente al ibuprofeno", "Es una proteína muscular que mejora la movilidad articular"],
        correcta: 1,
        explicacion: "La condroitina es un componente estructural del cartílago. En combinación con glucosamina, la evidencia científica sugiere que puede ralentizar la degradación cartilaginosa en artrosis."
    },
    {
        pregunta: "¿Qué enfermedad comparte el acrónimo FIV con el VIH humano y por qué?",
        opciones: ["Son el mismo virus adaptado a diferentes huéspedes", "El virus de inmunodeficiencia felina pertenece al mismo grupo (lentivirus) que el VIH aunque no es transmisible entre especies", "Es una analogía errónea sin base científica", "Porque ambos se transmiten por la misma vía y tienen el mismo tratamiento"],
        correcta: 1,
        explicacion: "FIV y VIH son lentivirus retrovíricos y funcionan de forma análoga afectando al sistema inmune. No se transmiten entre gatos y humanos. Los gatos FIV+ pueden tener buena calidad de vida."
    },
    {
        pregunta: "¿Cuántas veces al año se recomienda realizar una revisión veterinaria a un perro mayor de 7 años?",
        opciones: ["Una vez al año es suficiente siempre", "Al menos dos veces al año", "Solo cuando presente síntomas visibles", "Mensualmente de forma preventiva"],
        correcta: 1,
        explicacion: "Los perros senior (mayores de 7 años en razas medianas, antes en razas grandes) deben revisarse cada 6 meses. El envejecimiento acelerado puede hacer que patologías evolucionen rápido."
    },
    {
        pregunta: "¿Qué es el comportamiento de caza simulada en gatos domésticos y qué función tiene?",
        opciones: ["Es una patología conductual que indica frustración severa", "Es un comportamiento innato de práctica de habilidades cinegéticas que necesitan expresar incluso sin hambre", "Solo ocurre en gatos que han cazado antes", "Es una señal de dominancia sobre el propietario"],
        correcta: 1,
        explicacion: "Los gatos tienen instinto cazador disociado del hambre. La caza simulada (con juguetes, plumas, ratones de tela) es necesaria para su bienestar mental incluso en gatos saciados."
    },
    {
        pregunta: "¿Qué es la hipercalcemia en gatos y qué la puede causar?",
        opciones: ["Exceso de calcio en sangre, asociado a linfoma, hipervitaminosis D o enfermedad renal crónica", "Deficiencia de calcio que provoca convulsiones en gatas lactantes", "Un exceso de fósforo que mineraliza los riñones", "Una infección parasitaria que afecta a los huesos"],
        correcta: 0,
        explicacion: "La hipercalcemia felina idiopática es frecuente. También puede ser paraneoplásica (linfoma), por hipervitaminosis D o ERC. Causa letargia, vómitos, poliuria y cálculos renales."
    },
    {
        pregunta: "¿Cuál es la principal diferencia entre la desparasitación interna y externa en perros?",
        opciones: ["La interna elimina garrapatas y la externa pulgas", "La interna actúa contra helmintos (gusanos) y la externa contra ectoparásitos (pulgas, garrapatas, ácaros)", "Son idénticas en composición pero se aplican de forma diferente", "Solo es necesaria la externa en perros de interior"],
        correcta: 1,
        explicacion: "La desparasitación interna usa antihelmínticos contra parásitos digestivos y sistémicos. La externa usa insecticidas y acaricidas topicos o en collar. Ambas son necesarias y complementarias."
    },
    {
        pregunta: "Un gato que ronronea mientras está en la consulta veterinaria siendo manipulado...",
        opciones: ["Está completamente cómodo y relajado", "Puede estar ronroneando por estrés o miedo, ya que el ronroneo no siempre indica bienestar", "Está en modo de caza", "Indica que es un gato muy sociable con personas desconocidas"],
        correcta: 1,
        explicacion: "El ronroneo puede indicar bienestar pero también estrés, miedo o incluso dolor. Los gatos ronronean en situaciones de alta tensión como mecanismo de autorregulación."
    },
    {
        pregunta: "¿Qué es el síndrome de Wobbler y qué razas lo padecen frecuentemente?",
        opciones: ["Una enfermedad neuromuscular del Dachshund por hernia discal", "Una compresión medular cervical frecuente en Doberman y razas grandes de cuello largo", "Una distrofia muscular específica del Labrador", "Una patología del oído interno que causa desequilibrio en razas toy"],
        correcta: 1,
        explicacion: "El síndrome de Wobbler (espondilopatía cervical) causa compresión de la médula espinal en el cuello. El Doberman y el Gran Danés son las razas más afectadas."
    },
    {
        pregunta: "¿Por qué no se recomienda dar hueso de pollo cocinado a los perros?",
        opciones: ["Porque tiene demasiado calcio para su dieta", "Porque la cocción los hace frágiles y astillables, pudiendo lacerar el tracto digestivo", "Porque el pollo es alergénico en perros", "Porque los huesos de pollo crudos tampoco son seguros"],
        correcta: 1,
        explicacion: "La cocción deshidrata el hueso y lo hace quebradizo. Las astillas pueden perforar esófago, estómago o intestino. Los huesos crudos se consideran más seguros pero no están exentos de riesgo."
    },
    {
        pregunta: "¿Qué es la esteatitis o enfermedad del tejido adiposo amarillo en gatos?",
        opciones: ["Una inflamación del tejido graso causada por exceso de ácidos grasos insaturados sin suficiente vitamina E", "Un tumor benigno de tejido adiposo", "Una lipidosis hepática secundaria a anorexia", "Una infección subcutánea bacteriana"],
        correcta: 0,
        explicacion: "La esteatitis felina se asocia a dietas altas en atún o pescado azul sin suficiente vitamina E. Causa dolor, fiebre y nódulos subcutáneos dolorosos. Hoy es menos frecuente."
    },
    {
        pregunta: "¿Cuál de estos signos NO es típico de un golpe de calor en perros?",
        opciones: ["Jadeo intenso y continuo", "Temperatura rectal por encima de 40,5 °C", "Salivación excesiva", "Extremidades frías y rigidez muscular generalizada"],
        correcta: 3,
        explicacion: "Las extremidades frías en golpe de calor pueden indicar colapso circulatorio en fase terminal. Los signos típicos son jadeo, babeo, mucosas rojas o grises y temperatura elevada."
    },
    {
        pregunta: "¿Qué es el síndrome de Cushing en perros y cuál es su causa más frecuente?",
        opciones: ["Un hipotiroidismo grave; la causa más frecuente es autoinmune", "Un hipercortisolismo; la causa más frecuente es un microadenoma hipofisario", "Una hipoglucemia crónica; la causa más frecuente es un insulinoma", "Una hiperparatiroidismo; la causa más frecuente es una dieta deficiente en fósforo"],
        correcta: 1,
        explicacion: "El síndrome de Cushing (hiperadrenocorticismo) es causado por exceso de cortisol. En el 85% de los casos se debe a un tumor hipofisario. Causa polidipsia, poliuria, abdomen péndulo y alopecia."
    },
    {
        pregunta: "¿Qué significa que un perro tenga 'ojos de ballena' en lenguaje de señales caninas?",
        opciones: ["Que padece una enfermedad ocular hereditaria", "Que muestra el blanco del ojo (esclerótica) indicando incomodidad o estrés", "Que está muy atento y concentrado en algo", "Que tiene ojos de color azul claro, frecuente en algunas razas"],
        correcta: 1,
        explicacion: "El 'whale eye' o ojo de ballena es una señal de estrés o incomodidad en perros. El animal gira la cabeza pero mantiene la mirada fija, mostrando la esclerótica blanca."
    },
    {
        pregunta: "¿Qué es el método LIMA en adiestramiento canino?",
        opciones: ["Una técnica de condicionamiento clásico pavloviano", "Least Intrusive, Minimally Aversive: usar el método menos intrusivo y aversivo efectivo disponible", "Un sistema de entrenamiento basado en refuerzo negativo exclusivamente", "Una certificación internacional de entrenadores caninos"],
        correcta: 1,
        explicacion: "LIMA (Least Intrusive, Minimally Aversive) es un principio ético del adiestramiento moderno que prioriza métodos de refuerzo positivo antes de recurrir a cualquier técnica aversiva."
    },
    {
        pregunta: "¿Cuál es la diferencia entre condicionamiento clásico y operante en el aprendizaje animal?",
        opciones: ["El clásico usa premios y el operante castigos", "El clásico asocia estímulos (Pavlov); el operante modifica conductas por sus consecuencias (Skinner)", "El clásico es para perros y el operante para gatos", "El operante es más antiguo y menos eficaz que el clásico"],
        correcta: 1,
        explicacion: "El condicionamiento clásico (Pavlov) crea asociaciones entre estímulos. El operante (Skinner) modifica la probabilidad de una conducta según sus consecuencias (refuerzo o castigo)."
    },
    {
        pregunta: "¿Por qué los gatos negros son los que más tiempo pasan en protectoras estadísticamente?",
        opciones: ["Son más agresivos que otras coloraciones", "Por supersticiones culturales y porque se fotografían peor en webs de adopción", "Tienen peor salud que gatos de otros colores", "Las protectoras los registran en último lugar por protocolo"],
        correcta: 1,
        explicacion: "El 'síndrome del gato negro' está documentado. Las supersticiones, la mala visibilidad en fotos digitales y la falta de rasgos faciales diferenciadores alargan su estancia en protectoras."
    },
    {
        pregunta: "¿Qué es el enriquecimiento ambiental para gatos de interior y por qué es importante?",
        opciones: ["Añadir plantas de interior a la vivienda para mejorar el ambiente", "Proveer estímulos físicos, olfativos y cognitivos que satisfagan sus necesidades conductuales innatas", "Aumentar el espacio disponible eliminando muebles", "Introducir otro gato para que socialice"],
        correcta: 1,
        explicacion: "El enriquecimiento ambiental incluye rascadores, alturas, escondites, juego interactivo y estímulos olfativos. Sin él, los gatos de interior pueden desarrollar estrés, agresividad o FLUTD."
    },
    {
        pregunta: "¿Cuál de estos síntomas puede indicar hipotiroidismo en un perro?",
        opciones: ["Pérdida de peso, hiperactividad y polidipsia", "Aumento de peso, letargia, intolerancia al frío y alopecia bilateral simétrica", "Poliuria, polifagia y adelgazamiento", "Temblores, convulsiones y mucosas pálidas"],
        correcta: 1,
        explicacion: "El hipotiroidismo canino es una de las endocrinopatías más frecuentes. Sus signos clásicos incluyen aumento de peso sin causa dietética, letargia, pelo opaco y alopecia sin picor."
    },
    {
        pregunta: "¿Qué función tiene el ronroneo en las relaciones entre humanos y gatos según investigaciones recientes?",
        opciones: ["Exclusivamente comunicar satisfacción", "Los gatos han desarrollado un ronroneo 'solicitante' con componente de alta frecuencia similar al llanto de bebé para obtener respuesta humana", "Es un mecanismo de defensa que inhibe conductas agresivas en humanos", "No tiene función comunicativa hacia humanos, solo entre gatos"],
        correcta: 1,
        explicacion: "Investigaciones de Karen McComb (2009) documentaron un ronroneo 'urgente' con componente de 220-520 Hz similar al llanto infantil que activa la respuesta de cuidado en humanos."
    },
    {
        pregunta: "¿Qué diferencia hay entre la rabia clásica furiosa y la paralítica en perros?",
        opciones: ["La furiosa es causada por una cepa diferente del virus", "La furiosa cursa con agresividad y excitación; la paralítica con parálisis ascendente sin fase de agresividad", "La paralítica solo afecta a perros vacunados parcialmente", "Son fases del mismo proceso pero se dan en razas diferentes"],
        correcta: 1,
        explicacion: "La rabia tiene dos presentaciones: la furiosa (agresividad, desorientación, hidrofobia) y la paralítica o muda (parálisis progresiva sin agresividad). Ambas son mortales."
    },
    {
        pregunta: "¿Qué es la espondiloartrosis en perros y en qué razas es más frecuente?",
        opciones: ["Una degeneración del cartílago articular periférico; más frecuente en razas toy", "Una calcificación de los ligamentos intervertebrales que limita la movilidad; más frecuente en perros grandes y de trabajo", "Una hernia discal aguda; más frecuente en razas condrodistróficas", "Una enfermedad autoinmune de la médula ósea; sin predisposición racial clara"],
        correcta: 1,
        explicacion: "La espondiloartrosis o espondilosis deformante es una calcificación vertebral degenerativa frecuente en Pastores Alemanes, Boxers y perros de trabajo. Puede ser asintomática o causar dolor."
    },
    {
        pregunta: "¿Cuál es el período de incubación típico del parvovirus canino?",
        opciones: ["24-48 horas", "3-7 días", "2-3 semanas", "Más de un mes"],
        correcta: 1,
        explicacion: "El período de incubación del parvovirus es de 3-7 días. El virus se elimina en heces antes de que aparezcan síntomas, lo que facilita su propagación en entornos con cachorros no vacunados."
    },
    {
        pregunta: "¿Por qué la adopción de un animal adulto puede tener ventajas sobre la de un cachorro?",
        opciones: ["Los adultos son siempre más obedientes por naturaleza", "El carácter está formado, el tamaño es definitivo y suelen estar desparasitados y esterilizados", "Los adultos no requieren socialización", "Las protectoras cobran menos por animales adultos"],
        correcta: 1,
        explicacion: "Adoptar un adulto permite conocer de antemano su personalidad y tamaño definitivo. Suelen venir con vacunas, esterilización y desparasitación al día, lo que reduce costes iniciales."
    },
    {
        pregunta: "¿Qué es la acné felina y dónde aparece habitualmente?",
        opciones: ["Una infección viral que produce vesículas en todo el cuerpo", "Una foliculitis que aparece principalmente en el mentón del gato", "Una alergia alimentaria que se manifiesta en el abdomen", "Una dermatosis hormonal que afecta al lomo"],
        correcta: 1,
        explicacion: "El acné felino se manifiesta con comedones y costras en el mentón y labio inferior. Puede relacionarse con el material del comedero, el estrés o la higiene. En casos graves requiere tratamiento tópico."
    },
    {
        pregunta: "¿Qué protocolo es correcto si un perro desconocido se acerca corriendo hacia ti?",
        opciones: ["Correr para alejarte y hacer ruido para que se detenga", "Quedarte quieto, evitar el contacto visual directo y girar ligeramente el cuerpo", "Extender la mano directamente para que te huela", "Agacharte a su altura para parecer menos amenazante"],
        correcta: 1,
        explicacion: "Quedarse quieto, evitar la mirada directa y girar el cuerpo reduce la percepción de amenaza. Correr activa el instinto de persecución. Agacharse puede interpretarse como posición de lucha."
    },
    {
        pregunta: "¿Cuál es la causa más frecuente de muerte en gatos mayores de 10 años?",
        opciones: ["Cardiomiopatía hipertrófica", "Enfermedad renal crónica", "Linfoma intestinal", "Diabetes mellitus"],
        correcta: 1,
        explicacion: "La enfermedad renal crónica (ERC) es la principal causa de muerte en gatos mayores. Se estima que afecta a más del 30% de los gatos mayores de 15 años."
    },
    {
        pregunta: "¿Qué es la laringotraqueobronquitis infecciosa canina (tos de las perreras)?",
        opciones: ["Una infección exclusivamente bacteriana por Bordetella bronchiseptica", "Un síndrome respiratorio multietiológico que puede involucrar varios virus y bacterias", "Una variante canina de la gripe humana estacional", "Una enfermedad exclusiva de perros no vacunados"],
        correcta: 1,
        explicacion: "La tos de las perreras es un síndrome multifactorial. Los agentes más frecuentes incluyen Bordetella bronchiseptica, parainfluenza canina, adenovirus tipo 2 y otros. La vacuna protege contra los principales."
    },
    {
        pregunta: "¿Qué efecto tiene la castración en la conducta de un perro macho con comportamientos territoriales o de monta?",
        opciones: ["Elimina completamente estos comportamientos en el 100% de los casos", "Puede reducirlos si tienen base hormonal, pero no los elimina si ya están aprendidos", "No tiene ningún efecto en la conducta, solo en la reproducción", "Aumenta la agresividad como compensación hormonal"],
        correcta: 1,
        explicacion: "La castración reduce conductas dependientes de testosterona si tienen base hormonal, pero no modifica comportamientos aprendidos ya instaurados. El adiestramiento sigue siendo necesario."
    },
    {
        pregunta: "¿Qué significa la sigla WSAVA en relación con la medicina veterinaria?",
        opciones: ["Western Society for Animal Vaccination and Assessment", "World Small Animal Veterinary Association, que publica guías internacionales de vacunación y nutrición", "Wildlife and Shelter Animal Veterinary Alliance", "World Standards Association for Veterinary Audits"],
        correcta: 1,
        explicacion: "La WSAVA es la asociación veterinaria internacional de referencia para pequeños animales. Sus guías de vacunación y nutrición son el estándar global para veterinarios de compañía."
    },
    {
        pregunta: "¿Cuál es la diferencia entre una protectora y un CPA (Centro de Protección Animal)?",
        opciones: ["Son términos completamente equivalentes", "Los CPA son centros de gestión municipal u oficial; las protectoras suelen ser entidades privadas sin ánimo de lucro", "Los CPA atienden solo a animales exóticos", "Las protectoras tienen obligación legal de sacrificio cero; los CPA no"],
        correcta: 1,
        explicacion: "Los Centros de Protección Animal suelen ser de titularidad municipal o pública. Las protectoras son asociaciones privadas sin ánimo de lucro. Ambos pueden colaborar pero tienen gestiones distintas."
    },
    {
        pregunta: "¿Qué es el efecto fundador en razas caninas y qué problema genera?",
        opciones: ["El reconocimiento inicial de una raza por un único criador", "La reducción de diversidad genética al establecer una raza a partir de pocos individuos, aumentando enfermedades hereditarias", "La tendencia de los perros a seguir al primer humano que los alimenta", "Un fenómeno de impronta que ocurre en la primera semana de vida"],
        correcta: 1,
        explicacion: "El efecto fundador en razas cerradas concentra alelos deletéreos. Razas con base genética estrecha (Dálmata, Basenji) tienen alta prevalencia de enfermedades hereditarias específicas."
    },
    {
        pregunta: "¿Cuál es el riesgo específico del síndrome de dilatación-vólvulo gástrico en perros?",
        opciones: ["Que solo afecta a perros obesos y sedentarios", "Que el estómago se dilata y rota cortando el flujo sanguíneo, causando necrosis y muerte en horas sin cirugía", "Que es asintomático y solo se detecta en radiografías de rutina", "Que afecta principalmente a razas braquicéfalas"],
        correcta: 1,
        explicacion: "El GDV (dilatación-vólvulo gástrico) es una emergencia con mortalidad muy alta sin cirugía urgente. Afecta más a razas grandes de pecho profundo. Ejercicio intenso tras comer es un factor de riesgo."
    },
    {
        pregunta: "¿Qué es la clicker training y en qué principio del aprendizaje se basa?",
        opciones: ["Un método de condicionamiento clásico que usa el sonido para inducir calma", "Una técnica de refuerzo positivo basada en el condicionamiento operante que usa el clic como reforzador secundario marcador", "Un entrenamiento aversivo que usa sonidos para interrumpir conductas no deseadas", "Una técnica de modificación conductual para perros con fobias"],
        correcta: 1,
        explicacion: "El clicker es un marcador condicionado (reforzador secundario) que señala con precisión el momento exacto de la conducta correcta. Se basa en el condicionamiento operante de Skinner."
    },
    {
        pregunta: "¿Cuántos años vive de media un perro de raza grande como el Gran Danés?",
        opciones: ["15-18 años", "12-15 años", "7-10 años", "5-7 años"],
        correcta: 2,
        explicacion: "Las razas gigantes envejecen más rápido. El Gran Danés tiene una esperanza de vida de 7-10 años. Las razas pequeñas como el Chihuahua pueden vivir 15-20 años."
    },
    {
        pregunta: "¿Qué indica la presencia de cristales de estruvita en la orina de un gato?",
        opciones: ["Un funcionamiento renal perfectamente normal", "Predisposición a formar cálculos urinarios; relacionado con pH urinario alcalino e infecciones", "Una hipervitaminosis que requiere dieta de exclusión", "Una infección parasitaria del tracto urinario"],
        correcta: 1,
        explicacion: "Los cristales de estruvita (fosfato amónico magnésico) se forman en orina alcalina, frecuentemente asociados a infecciones bacterianas. Pueden causar obstrucciones, especialmente en machos."
    },
    {
        pregunta: "¿Qué ley estatal española regula actualmente la protección y tenencia de animales de compañía?",
        opciones: ["La Ley de Protección Animal de 1987 aún vigente", "La Ley 7/2023 de Bienestar Animal", "El Real Decreto 1515/2018 de Tenencia de Animales", "La Ley Orgánica 4/2015 de Seguridad Ciudadana"],
        correcta: 1,
        explicacion: "La Ley 7/2023 de 28 de marzo, de Bienestar Animal, es la normativa estatal vigente. Prohíbe el sacrificio de animales sanos o recuperables y regula la tenencia, entre otras cuestiones."
    },
    {
        pregunta: "¿Qué es la bronquitis crónica felina (asma del gato) y cómo se maneja?",
        opciones: ["Una infección bacteriana recurrente tratada con antibióticos a largo plazo", "Una inflamación crónica de las vías aéreas con componente alérgico, manejada con corticosteroides y broncodilatadores", "Una parasitosis pulmonar tratada con antiparasitarios", "Una malformación congénita de la tráquea sin tratamiento efectivo"],
        correcta: 1,
        explicacion: "El asma felina es una bronconeumopatía crónica con hiperreactividad de las vías aéreas. El tratamiento incluye corticosteroides inhalados o sistémicos y broncodilatadores en crisis."
    },
    {
        pregunta: "¿Qué indica que un perro enseña los dientes sin gruñir cuando se le acerca alguien?",
        opciones: ["Es siempre una señal de agresividad inminente", "Puede ser una sonrisa sumisa o puede ser una advertencia; el contexto y el resto del lenguaje corporal son determinantes", "Es una señal de juego y bienvenida en todas las razas", "Indica que el perro fue mal socializado en las primeras semanas"],
        correcta: 1,
        explicacion: "Enseñar los dientes puede ser una sonrisa sumisa (gesto apaciguador) o una amenaza. El contexto, la postura corporal, las orejas y el rabo son esenciales para interpretarlo correctamente."
    },
    {
        pregunta: "¿Qué es la hepatitis infecciosa canina y cuál es su agente causal?",
        opciones: ["Una inflamación hepática causada por la bacteria Leptospira interrogans", "Una enfermedad vírica causada por el Adenovirus Canino Tipo 1 (CAV-1)", "Una parasitosis hepática causada por Toxocara canis", "Una intoxicación alimentaria que afecta al hígado"],
        correcta: 1,
        explicacion: "La hepatitis infecciosa canina (enfermedad de Rubarth) es causada por CAV-1. La vacuna actual usa CAV-2 que ofrece protección cruzada. Se incluye en la polivalente estándar."
    },
    {
        pregunta: "¿Cuál es el impacto aproximado de los gatos domésticos y asilvestrados en la fauna silvestre según estudios en España?",
        opciones: ["Es mínimo porque los gatos prefieren presas domésticas", "Significativo: son uno de los principales factores de mortalidad no natural de aves y pequeños mamíferos silvestres", "Solo relevante en islas donde no hay depredadores nativos", "Solo afecta a especies invasoras, no a especies protegidas"],
        correcta: 1,
        explicacion: "Los estudios cifran el impacto en millones de aves y reptiles anualmente en España. Los gatos ferales y los domésticos con acceso al exterior son una amenaza para la biodiversidad reconocida por organismos como la UICN."
    },
    {
        pregunta: "¿Qué es la criptorquidia en perros y por qué tiene relevancia clínica?",
        opciones: ["Una inflamación del escroto de causa parasitaria sin mayor importancia", "La retención de uno o ambos testículos en el canal inguinal o abdomen, con mayor riesgo de torsión testicular y neoplasia", "Una deformidad de la vejiga urinaria frecuente en razas braquicéfalas", "Una anomalía del pene sin repercusión en la fertilidad"],
        correcta: 1,
        explicacion: "El testículo retenido tiene 13 veces más riesgo de desarrollar tumor que un testículo descendido normal. Se recomienda castración preventiva en perros criptórquidos."
    },
    {
        pregunta: "¿Cuándo fue abolido el sacrificio de perros y gatos en perreras en la Comunidad de Madrid?",
        opciones: ["En 1989 con la primera ley autonómica de protección animal", "En 2003 mediante la Ley 1/1990 modificada", "En 1994 con la Ley 1/1990, siendo de las primeras en España en introducir esta prohibición", "No está abolido, se mantiene en casos de sobrepoblación"],
        correcta: 2,
        explicacion: "Madrid fue pionera en España. La Ley 1/1990 introdujo la prohibición del sacrificio de perros y gatos sanos, aunque con matices que se fueron desarrollando en sucesivas modificaciones."
    },
    {
        pregunta: "¿Qué es la presión osmótica de la orina y por qué es relevante en la dieta de un gato?",
        opciones: ["Determina el color de la orina pero no tiene implicaciones clínicas", "Indica la concentración urinaria; los gatos tienen riñones adaptados a orinas muy concentradas y una baja ingesta de agua espontánea, lo que les predispone a urolitiasis si se alimentan solo con pienso seco", "Solo es relevante en gatos con diabetes insípida", "Es un parámetro exclusivo de análisis de orina en laboratorio sin valor preventivo"],
        correcta: 1,
        explicacion: "Los gatos son felinos del desierto evolutivamente. Tienen baja sed espontánea. Si solo comen pienso seco, la ingesta hídrica puede ser insuficiente, concentrando la orina y favoreciendo cristales y cálculos."
    },
    {
        pregunta: "¿Qué es el virus del herpes felino (FHV-1) y cómo se gestiona en gatos portadores?",
        opciones: ["Un retrovirus que causa inmunodeficiencia similar al FIV pero más leve", "Un herpesvirus que causa rinotraqueítis y que, una vez infectado el gato, permanece latente de por vida con reactivaciones en situaciones de estrés", "Una enfermedad erradicable con vacunación completa", "Un parásito intracelular que afecta exclusivamente a los ojos"],
        correcta: 1,
        explicacion: "El FHV-1 produce infecciones respiratorias y oculares. Como todo herpesvirus, establece latencia en ganglios nerviosos. El estrés reactiva la infección. La vacuna reduce síntomas pero no evita la infección."
    },
    {
        pregunta: "¿Cuándo deben recibir los cachorros su primera vacuna según el protocolo WSAVA actualizado?",
        opciones: ["A las 4 semanas de vida", "Entre las 6 y 8 semanas de vida", "A los 3 meses exactamente", "Cuando se separan de la madre, independientemente de la edad"],
        correcta: 1,
        explicacion: "El protocolo WSAVA recomienda iniciar la vacunación a las 6-8 semanas. Los anticuerpos maternos pueden interferir con la vacuna; por eso se administran varias dosis hasta las 16 semanas."
    },
    {
        pregunta: "¿Qué es la dieta BARF y cuál es su principal controversia científica?",
        opciones: ["Una dieta hipocalórica para perros obesos; la controversia es su palatabilidad", "Una dieta basada en alimentos crudos (hueso, carne, vísceras); la controversia incluye el riesgo bacteriológico para el animal y el entorno humano, y la dificultad de equilibrarla nutricionalmente", "Una dieta hipoalergénica de prescripción veterinaria; la controversia es su alto coste", "Una dieta exclusivamente vegana; la controversia es su falta de aminoácidos esenciales"],
        correcta: 1,
        explicacion: "BARF (Biologically Appropriate Raw Food) es polémica porque estudios detectan Salmonella y E. coli en alimentos y heces de animales que la consumen, con riesgo para personas inmunocomprometidas."
    },
    {
        pregunta: "¿Qué parte del plan vacunal se considera 'core' (obligatoria para todos) por la WSAVA en perros?",
        opciones: ["Solo rabia en países endémicos", "Distemper, Parvovirus, Adenovirus y Rabia (donde es obligatoria legalmente)", "Leptospira, Bordetella y Borrelia además de las básicas", "Todas las vacunas disponibles se consideran core en perros"],
        correcta: 1,
        explicacion: "Las vacunas core caninas según WSAVA son: Distemper (moquillo), Parvovirus tipo 2, Adenovirus tipo 2 (y protección cruzada frente a CAV-1) y Rabia donde sea legalmente exigible."
    }
];


// ================================================================
// TEST DE COMPATIBILIDAD

/* Atributos de compatibilidad (fijos por diseño del test) */
var atributosBase = {
    1: { espacio: 1, soledad: 2, actividad: 0, experiencia: 1, ninos: 2, otrosAnimales: 1, ruido: 0, pelaje: 1, presupuesto: 2, edadPref: 3, caracter: 2 },
    2: { espacio: 2, soledad: 2, actividad: 2, experiencia: 1, ninos: 2, otrosAnimales: 1, ruido: 1, pelaje: 1, presupuesto: 1, edadPref: 2, caracter: 2 },
    3: { espacio: 1, soledad: 2, actividad: 2, experiencia: 1, ninos: 2, otrosAnimales: 2, ruido: 1, pelaje: 1, presupuesto: 1, edadPref: 0, caracter: 2 },
    4: { espacio: 1, soledad: 3, actividad: 1, experiencia: 0, ninos: 1, otrosAnimales: 1, ruido: 0, pelaje: 1, presupuesto: 1, edadPref: 1, caracter: 0 },
    5: { espacio: 3, soledad: 0, actividad: 3, experiencia: 2, ninos: 2, otrosAnimales: 1, ruido: 2, pelaje: 2, presupuesto: 2, edadPref: 2, caracter: 3 },
    6: { espacio: 1, soledad: 1, actividad: 2, experiencia: 2, ninos: 0, otrosAnimales: 0, ruido: 1, pelaje: 1, presupuesto: 3, edadPref: 0, caracter: 1 }
};

/* Catálogo dinámico — se carga desde BD al iniciar */
var catalogoMascotas = [];

function cargarCatalogoMascotas(callback) {
    fetch('../backend/mascotas/listar.php?pagina=1&especie=todos')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.mascotas) { if (callback) callback(); return; }
            catalogoMascotas = data.mascotas.map(function(m) {
                /* Para animales nuevos, inferir atributos desde sus datos */
                var attrsAuto = {
                    espacio:       m.tamanyo === 'grande' ? 2 : m.tamanyo === 'pequeno' || m.tamanyo === 'pequeño' ? 0 : 1,
                    soledad:       m.especie === 'gato' ? 2 : 1,
                    actividad:     m.especie === 'perro' ? (parseInt(m.edad_anos) > 8 ? 1 : 2) : 1,
                    experiencia:   (m.estado_salud && m.estado_salud.toLowerCase() !== 'bueno') ? 2 : 1,
                    ninos:         m.compatible_ninos ? 2 : 0,
                    otrosAnimales: (m.compatible_perros || m.compatible_gatos) ? 2 : 0,
                    ruido:         m.especie === 'gato' ? 0 : 1,
                    pelaje:        1,
                    presupuesto:   (m.estado_salud && m.estado_salud.toLowerCase() !== 'bueno') ? 3 : 1,
                    edadPref:      parseInt(m.edad_anos) > 8 ? 3 : parseInt(m.edad_anos) > 3 ? 2 : 1,
                    caracter:      m.compatible_ninos ? 2 : 1
                };
                var attrs = atributosBase[m.idMascota] || attrsAuto;
                return {
                    id:          m.idMascota,
                    nombre:      m.nombre,
                    especie:     m.especie,
                    raza:        m.raza || '',
                    edadAnios:   parseInt(m.edad_anos) || 0,
                    edadTexto:   m.edad_texto || (m.edad_anos ? m.edad_anos + ' años' : ''),
                    protectora:  m.protectora_nombre || '',
                    icono:       m.especie === 'perro' ? 'fa-dog' : 'fa-cat',
                    foto:        m.foto_principal ? '../' + m.foto_principal : '../img/mascotas/default.jpg',
                    descripcion: m.descripcion || '',
                    atributos:   attrs
                };
            });
            if (callback) callback();
        })
        .catch(function() { if (callback) callback(); });
}
// ----------------------------------------------------------------
// PREGUNTAS DEL TEST DE COMPATIBILIDAD
var preguntasCompatibilidad = [
    {
        id: 'especie',
        pregunta: '¿Qué tipo de animal estás buscando?',
        descripcion: 'Esto nos permite centrarnos en los que mejor encajan contigo.',
        opciones: [
            { texto: 'Quiero un perro', valor: 'perro' },
            { texto: 'Quiero un gato', valor: 'gato' },
            { texto: 'No tengo preferencia, quiero ver qué encaja mejor', valor: 'indiferente' }
        ]
    },
    {
        id: 'espacio',
        pregunta: '¿Cómo es tu vivienda?',
        descripcion: 'El espacio disponible influye mucho en el bienestar del animal.',
        opciones: [
            { texto: 'Piso pequeño sin terraza ni exterior', valor: 0 },
            { texto: 'Piso mediano con terraza o balcón', valor: 1 },
            { texto: 'Piso grande o acceso a zona exterior compartida', valor: 2 },
            { texto: 'Casa con jardín o patio propio', valor: 3 }
        ]
    },
    {
        id: 'soledad',
        pregunta: '¿Cuántas horas al día estaría el animal solo en casa?',
        descripcion: 'Algunos animales toleran mejor la soledad que otros.',
        opciones: [
            { texto: 'Nunca o casi nunca (trabajo en casa / alguien siempre presente)', valor: 3 },
            { texto: 'Menos de 4 horas al día', valor: 2 },
            { texto: 'Entre 4 y 8 horas al día', valor: 1 },
            { texto: 'Más de 8 horas al día', valor: 0 }
        ]
    },
    {
        id: 'actividad',
        pregunta: '¿Cuánto ejercicio diario puedes ofrecer?',
        descripcion: 'Ser honesto aquí es clave para evitar frustraciones.',
        opciones: [
            { texto: 'Más de 1,5 horas de actividad al día (paseos, deporte, juego intenso)', valor: 3 },
            { texto: 'Entre 1 y 1,5 horas al día', valor: 2 },
            { texto: 'Entre 30 minutos y 1 hora al día', valor: 1 },
            { texto: 'Menos de 30 minutos o solo juego tranquilo en casa', valor: 0 }
        ]
    },
    {
        id: 'experiencia',
        pregunta: '¿Cuánta experiencia tienes con animales de compañía?',
        descripcion: 'Algunos animales requieren más conocimiento para su manejo.',
        opciones: [
            { texto: 'Ninguna, sería mi primera mascota', valor: 0 },
            { texto: 'Tuve mascotas de pequeño pero no siendo adulto', valor: 1 },
            { texto: 'He tenido perros o gatos siendo adulto', valor: 2 },
            { texto: 'Tengo experiencia con animales de necesidades especiales', valor: 3 }
        ]
    },
    {
        id: 'ninos',
        pregunta: '¿Hay niños o niñas en el hogar?',
        descripcion: 'Algunos animales conviven mejor con niños que otros.',
        opciones: [
            { texto: 'No hay niños en casa', valor: 3 },
            { texto: 'Sí, mayores de 12 años', valor: 3 },
            { texto: 'Sí, entre 6 y 12 años', valor: 2 },
            { texto: 'Sí, menores de 6 años', valor: 1 }
        ]
    },
    {
        id: 'otrosAnimales',
        pregunta: '¿Hay otros animales en tu hogar actualmente?',
        descripcion: 'La convivencia entre animales no siempre es sencilla.',
        opciones: [
            { texto: 'No, no hay otros animales', valor: 3 },
            { texto: 'Sí, un gato tranquilo y sociable', valor: 2 },
            { texto: 'Sí, un perro bien socializado', valor: 2 },
            { texto: 'Sí, varios animales o uno con carácter fuerte', valor: 1 }
        ]
    },
    {
        id: 'ruido',
        pregunta: '¿Cuánta tolerancia tienes al ruido o la vocalización?',
        descripcion: 'Ladrar o maullar frecuente puede ser un problema según el entorno.',
        opciones: [
            { texto: 'Sin problema, el entorno permite ruido', valor: 3 },
            { texto: 'Puedo tolerar algo de ladrido o maullido ocasional', valor: 2 },
            { texto: 'Prefiero un animal tranquilo y poco vocal', valor: 1 },
            { texto: 'Necesito un animal muy silencioso (vecinos sensibles, turnos nocturnos)', valor: 0 }
        ]
    },
    {
        id: 'pelaje',
        pregunta: '¿Cuánto tiempo puedes dedicar al mantenimiento del pelaje?',
        descripcion: 'El pelaje largo o abundante requiere cepillado frecuente.',
        opciones: [
            { texto: 'Mucho, no me importa cepillar, bañar o ir a la peluquería canina', valor: 3 },
            { texto: 'Algo, unos 15-20 minutos a la semana', valor: 2 },
            { texto: 'Poco, prefiero bajo mantenimiento', valor: 1 },
            { texto: 'Mínimo posible', valor: 0 }
        ]
    },
    {
        id: 'presupuesto',
        pregunta: '¿Cuál es tu presupuesto mensual aproximado para el animal?',
        descripcion: 'Incluye comida, veterinario, accesorios y posibles imprevistos.',
        opciones: [
            { texto: 'Menos de 30 € al mes', valor: 0 },
            { texto: 'Entre 30 y 60 € al mes', valor: 1 },
            { texto: 'Entre 60 y 120 € al mes', valor: 2 },
            { texto: 'Más de 120 € al mes, con margen para emergencias', valor: 3 }
        ]
    },
    {
        id: 'edadPref',
        pregunta: '¿Qué edad prefieres que tenga tu futura mascota?',
        descripcion: 'Cada etapa vital tiene sus ventajas y sus retos.',
        opciones: [
            { texto: 'Cachorro o gatito (menos de 1 año): mucha energía, requiere más dedicación', valor: 0 },
            { texto: 'Adulto joven (1-4 años): ya formado, activo', valor: 1 },
            { texto: 'Adulto maduro (5-9 años): equilibrado, menos imprevisible', valor: 2 },
            { texto: 'Senior (10 años o más): tranquilo, muy agradecido', valor: 3 }
        ]
    },
    {
        id: 'caracter',
        pregunta: '¿Qué tipo de vínculo buscas con tu mascota?',
        descripcion: 'Sin respuestas correctas, solo preferencias personales.',
        opciones: [
            { texto: 'Muy independiente, que no necesite atención constante', valor: 0 },
            { texto: 'Algo cariñoso pero con su propio espacio', valor: 1 },
            { texto: 'Cariñoso y que disfrute de la compañía', valor: 2 },
            { texto: 'Muy dependiente, siempre cerca de mí', valor: 3 }
        ]
    }
];

// ----------------------------------------------------------------
// ESTADO INTERNO
// ----------------------------------------------------------------
var estadoTest = {
    conocimiento: { preguntaActual: 0, respuestas: [], puntuacion: 0, seleccion: [] },
    compatibilidad: { preguntaActual: 0, respuestas: {}, especieFiltro: 'indiferente' }
};


function seleccionarPreguntas() {
    var copia = todasLasPreguntas.slice();
    for (var i = copia.length - 1; i > 0; i--) {
        var j = Math.floor(Math.random() * (i + 1));
        var tmp = copia[i]; copia[i] = copia[j]; copia[j] = tmp;
    }
    return copia.slice(0, 15);
}

// ----------------------------------------------------------------
// NAVEGACIÓN
function iniciarTest(tipo) {
    /* El catálogo solo es necesario para compatibilidad */
    if (tipo === 'compatibilidad' && catalogoMascotas.length === 0) {
        cargarCatalogoMascotas(function() { iniciarTest(tipo); });
        return;
    }
    document.getElementById('test-selector').classList.add('d-none');
    document.getElementById('test-' + tipo).classList.remove('d-none');
    if (tipo === 'conocimiento') {
        estadoTest.conocimiento = { preguntaActual: 0, respuestas: [], puntuacion: 0, seleccion: seleccionarPreguntas() };
        document.getElementById('resultado-conocimiento').classList.add('d-none');
        document.getElementById('preguntas-conocimiento').classList.remove('d-none');
        document.getElementById('btn-siguiente-conocimiento').classList.remove('d-none');
        renderPregunta(tipo);
    } else {
        estadoTest.compatibilidad = { preguntaActual: 0, respuestas: {}, especieFiltro: 'indiferente' };
        document.getElementById('resultado-compatibilidad').classList.add('d-none');
        document.getElementById('aviso-login-compatibilidad').classList.add('d-none');
        document.getElementById('compat-seleccion-especie').classList.remove('d-none');
        document.getElementById('prog-wrap-compatibilidad').classList.add('d-none');
        document.getElementById('preguntas-compatibilidad').classList.add('d-none');
        document.getElementById('btn-siguiente-compatibilidad').classList.add('d-none');
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function volverSelector() {
    document.getElementById('test-conocimiento').classList.add('d-none');
    document.getElementById('test-compatibilidad').classList.add('d-none');
    document.getElementById('test-selector').classList.remove('d-none');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function reiniciarTest(tipo) {
    if (tipo === 'conocimiento') {
        estadoTest.conocimiento = { preguntaActual: 0, respuestas: [], puntuacion: 0, seleccion: seleccionarPreguntas() };
        document.getElementById('resultado-conocimiento').classList.add('d-none');
        document.getElementById('preguntas-conocimiento').classList.remove('d-none');
        document.getElementById('btn-siguiente-conocimiento').classList.remove('d-none');
        renderPregunta(tipo);
    } else {
        estadoTest.compatibilidad = { preguntaActual: 0, respuestas: {}, especieFiltro: 'indiferente' };
        document.getElementById('resultado-compatibilidad').classList.add('d-none');
        document.getElementById('aviso-login-compatibilidad').classList.add('d-none');
        document.getElementById('compat-seleccion-especie').classList.remove('d-none');
        document.getElementById('prog-wrap-compatibilidad').classList.add('d-none');
        document.getElementById('preguntas-compatibilidad').classList.add('d-none');
        document.getElementById('btn-siguiente-compatibilidad').classList.add('d-none');
    }
}


function renderPregunta(tipo) {
    var preguntas = tipo === 'conocimiento'
        ? estadoTest.conocimiento.seleccion
        : preguntasCompatibilidad;
    var estado = estadoTest[tipo];
    var actual  = estado.preguntaActual;
    /* Para compatibilidad: total de preguntas excluyendo la 0 (especie) */
    var inicio  = tipo === 'compatibilidad' ? 1 : 0;
    var total   = preguntas.length;
    var numMostrado = actual - inicio + 1;
    var totalMostrado = total - inicio;

    var pct = ((actual - inicio) / (total - inicio)) * 100;
    document.getElementById('prog-' + tipo).style.width = pct + '%';
    document.getElementById('prog-label-' + tipo).textContent =
        'Pregunta ' + numMostrado + ' de ' + totalMostrado;
    document.getElementById('btn-siguiente-' + tipo).disabled = true;

    var pregunta = preguntas[actual];
    var html = '<div class="test-pregunta-bloque">';
    html += '<p class="test-pregunta-numero">Pregunta ' + numMostrado + ' / ' + totalMostrado + '</p>';
    if (tipo === 'compatibilidad' && pregunta.descripcion) {
        html += '<p class="test-pregunta-desc">' + pregunta.descripcion + '</p>';
    }

    html += '<h3 class="test-pregunta-texto">' + pregunta.pregunta + '</h3>';
    html += '<ul class="test-opciones">';
    pregunta.opciones.forEach(function(opcion, idx) {
        var texto = tipo === 'conocimiento' ? opcion : opcion.texto;
        html += '<li class="test-opcion" onclick="seleccionarOpcion(\'' + tipo + '\', ' + idx + ', this)">';
        html += '<span class="test-opcion-letra">' + String.fromCharCode(65 + idx) + '</span>';
        html += '<span class="test-opcion-texto">' + texto + '</span>';
        html += '</li>';
    });
    html += '</ul></div>';
    document.getElementById('preguntas-' + tipo).innerHTML = html;
}


function seleccionarOpcion(tipo, idx, elemento) {
    document.querySelectorAll('#test-' + tipo + ' .test-opcion').forEach(function(op) {
        op.classList.remove('seleccionada');
    });
    elemento.classList.add('seleccionada');
    estadoTest[tipo].respuestas[estadoTest[tipo].preguntaActual] = idx;
    document.getElementById('btn-siguiente-' + tipo).disabled = false;
}

function siguientePregunta(tipo) {
    var estado = estadoTest[tipo];
    var preguntas = tipo === 'conocimiento' ? estado.seleccion : preguntasCompatibilidad;
    var respuestaIdx = estado.respuestas[estado.preguntaActual];
    if (respuestaIdx === undefined) return;

    if (tipo === 'conocimiento') {
        if (respuestaIdx === preguntas[estado.preguntaActual].correcta) {
            estado.puntuacion++;
        }
    }

    estado.preguntaActual++;

    if (estado.preguntaActual < preguntas.length) {
        renderPregunta(tipo);
    } else {
        document.getElementById('preguntas-' + tipo).innerHTML = '';
        document.getElementById('btn-siguiente-' + tipo).classList.add('d-none');
        document.getElementById('prog-' + tipo).style.width = '100%';
        document.getElementById('prog-label-' + tipo).textContent = 'Completado';
        tipo === 'conocimiento' ? mostrarResultadoConocimiento() : mostrarResultadoCompatibilidad();
    }
}

// ----------------------------------------------------------------
// RESULTADO CONOCIMIENTO
function mostrarResultadoConocimiento() {
    var puntos = estadoTest.conocimiento.puntuacion;
    var total = estadoTest.conocimiento.seleccion.length;
    var pct = Math.round((puntos / total) * 100);
    var icono, titulo, mensaje, clase;

    if (pct >= 80) {
        icono = '<i class="fa-solid fa-trophy"></i>';
        titulo = '¡Excelente!';
        mensaje = 'Tienes un conocimiento muy sólido sobre el cuidado animal. Las mascotas están en buenas manos contigo.';
        clase = 'resultado-excelente';
    } else if (pct >= 50) {
        icono = '<i class="fa-solid fa-star-half-stroke"></i>';
        titulo = '¡Bien hecho!';
        mensaje = 'Tienes una base buena, pero aún hay margen para aprender. Revisa las respuestas incorrectas.';
        clase = 'resultado-bien';
    } else {
        icono = '<i class="fa-solid fa-seedling"></i>';
        titulo = 'Hay que estudiar un poco más';
        mensaje = 'No te preocupes, lo importante es querer aprender. Repasa los conceptos básicos de cuidado animal.';
        clase = 'resultado-mejorar';
    }

    document.getElementById('res-icon-conocimiento').innerHTML = icono;
    document.getElementById('res-icon-conocimiento').className = 'test-resultado-icono ' + clase;
    document.getElementById('res-titulo-conocimiento').textContent = titulo;
    document.getElementById('res-puntuacion-conocimiento').textContent = puntos + ' / ' + total + ' correctas (' + pct + '%)';
    document.getElementById('res-mensaje-conocimiento').textContent = mensaje;

    var detalle = '<h4 class="test-detalle-titulo">Revisión de respuestas</h4>';
    estadoTest.conocimiento.seleccion.forEach(function(preg, i) {
        var elegida = estadoTest.conocimiento.respuestas[i];
        var correcta = elegida === preg.correcta;
        detalle += '<div class="test-detalle-item ' + (correcta ? 'correcto' : 'incorrecto') + '">';
        detalle += '<p class="test-detalle-preg"><strong>' + (correcta ? '✓' : '✗') + ' ' + preg.pregunta + '</strong></p>';
        if (!correcta) {
            detalle += '<p class="test-detalle-resp">Tu respuesta: <em>' + preg.opciones[elegida] + '</em></p>';
            detalle += '<p class="test-detalle-resp">Correcta: <em>' + preg.opciones[preg.correcta] + '</em></p>';
        }
        detalle += '<p class="test-detalle-exp">' + preg.explicacion + '</p>';
        detalle += '</div>';
    });
    document.getElementById('res-detalle-conocimiento').innerHTML = detalle;
    document.getElementById('resultado-conocimiento').classList.remove('d-none');
    /* Guardar resultado en localStorage para perfil */
    localStorage.setItem('pf_test_conocimiento_' + _pfUserId(), JSON.stringify({
        fecha: new Date().toISOString(),
        puntos: puntos,
        total: total,
        pct: pct
    }));
}

// ----------------------------------------------------------------
// RESULTADO COMPATIBILIDAD
function mostrarResultadoCompatibilidad() {
    if (!_testLogueado) {
        document.getElementById('aviso-login-compatibilidad').classList.remove('d-none');
        return;
    }
    /* Guardar resultado en localStorage para perfil */
    localStorage.setItem('pf_test_compatibilidad_' + _pfUserId(), JSON.stringify({
        fecha: new Date().toISOString(),
        especieFiltro: estadoTest.compatibilidad.especieFiltro
    }));
    /* Si el catálogo no está cargado, cargarlo primero */
    if (catalogoMascotas.length === 0) {
        cargarCatalogoMascotas(calcularRanking);
    } else {
        calcularRanking();
    }
}

// ----------------------------------------------------------------
// CÁLCULO DEL RANKING
function calcularRanking() {
    var estado = estadoTest.compatibilidad;
    var especieFiltro = estado.especieFiltro;

    var atributosPreguntas = ['espacio','soledad','actividad','experiencia','ninos','otrosAnimales','ruido','pelaje','presupuesto','edadPref','caracter'];
    var perfilUsuario = {};

    atributosPreguntas.forEach(function(attr, i) {
        var idxPregunta = i + 1; // +1 porque pregunta 0 es especie
        var respuestaIdx = estado.respuestas[idxPregunta];
        if (respuestaIdx !== undefined) {
            perfilUsuario[attr] = preguntasCompatibilidad[idxPregunta].opciones[respuestaIdx].valor;
        } else {
            perfilUsuario[attr] = 1; // valor neutro si no respondió
        }
    });

    var catalogo = catalogoMascotas.filter(function(m) {
        if (especieFiltro === 'indiferente') return true;
        return m.especie === especieFiltro;
    });

    // Calcular % de compatibilidad para cada mascota
    var ranking = catalogo.map(function(mascota) {
        var sumaPuntos = 0;
        var totalAtributos = atributosPreguntas.length;

        atributosPreguntas.forEach(function(attr) {
            var valorUsuario = perfilUsuario[attr];
            var valorMascota = mascota.atributos[attr];
            var diferencia = Math.abs(valorUsuario - valorMascota);
            var puntuacionAtributo = Math.max(0, 3 - diferencia) / 3;
            sumaPuntos += puntuacionAtributo;
        });

        var compatibilidad = Math.round((sumaPuntos / totalAtributos) * 100);
        return { mascota: mascota, compatibilidad: compatibilidad };
    });

    // Ordenar de mayor a menor compatibilidad
    ranking.sort(function(a, b) { return b.compatibilidad - a.compatibilidad; });

    mostrarRanking(ranking, perfilUsuario, especieFiltro);
}

// ----------------------------------------------------------------
// RANKING
// ----------------------------------------------------------------
function mostrarRanking(ranking, perfilUsuario, especieFiltro) {
    var labelEspecie = especieFiltro === 'perro' ? 'perros' :
                       especieFiltro === 'gato'  ? 'gatos'  : 'perros y gatos';

    var mensaje = 'Hemos analizado tu perfil y ordenado los ' + labelEspecie + ' del catálogo de más a menos compatibles contigo. Cuanto mayor sea el porcentaje, mejor encaja ese animal con tu estilo de vida.';

    document.getElementById('res-mensaje-compatibilidad').textContent = mensaje;

    var html = '';

    ranking.forEach(function(item, idx) {
        var m = item.mascota;
        var pct = item.compatibilidad;
        var medallaClase = idx === 0 ? 'ranking-oro' : idx === 1 ? 'ranking-plata' : idx === 2 ? 'ranking-bronce' : 'ranking-normal';
        var medallaIcono = idx === 0 ? '🥇' : idx === 1 ? '🥈' : idx === 2 ? '🥉' : (idx + 1) + 'º';

        var colorBarra = pct >= 75 ? '#2e7d32' : pct >= 50 ? '#F8BA56' : '#bbb';

        html += '<div class="compat-card ' + medallaClase + '">';
        html += '  <div class="compat-card-medalla">' + medallaIcono + '</div>';
        if (m.foto) {
            html += '  <img src="' + m.foto + '" alt="' + m.nombre + '" class="compat-card-foto" style="width:56px;height:56px;border-radius:50%;object-fit:cover;object-position:center top;flex-shrink:0;" onerror="this.style.display=\'none\'">';
        } else {
            html += '  <div class="compat-card-icono"><i class="fa-solid ' + m.icono + '"></i></div>';
        }
        html += '  <div class="compat-card-info">';
        html += '    <div class="compat-card-nombre">' + m.nombre + ' <span class="compat-card-especie">' + m.especie + '</span></div>';
        html += '    <div class="compat-card-raza">' + m.raza + ' · ' + m.edadTexto + '</div>';
        html += '    <div class="compat-card-prot"><i class="fa-solid fa-shield-dog me-1"></i>' + m.protectora + '</div>';
        html += '    <div class="compat-card-desc">' + m.descripcion + '</div>';
        html += '    <div class="compat-barra-wrap">';
        html += '      <div class="compat-barra-fondo">';
        html += '        <div class="compat-barra-fill" style="width:' + pct + '%;background:' + colorBarra + '"></div>';
        html += '      </div>';
        html += '      <span class="compat-pct">' + pct + '% compatible</span>';
        html += '    </div>';
        html += '  </div>';
        html += '  <a href="fichaAnimal.html?id=' + m.id + '" class="btn-azul compat-card-btn">Ver ficha</a>';
        html += '</div>';
    });

    document.getElementById('res-mascotas-compatibilidad').innerHTML = html;
    document.getElementById('resultado-compatibilidad').classList.remove('d-none');

    /* Guardar ranking completo en localStorage para perfil */
    var rankingGuardar = ranking.map(function(item) {
        return {
            id:            item.mascota.id,
            nombre:        item.mascota.nombre,
            especie:       item.mascota.especie,
            raza:          item.mascota.raza,
            edadTexto:     item.mascota.edadTexto,
            protectora:    item.mascota.protectora,
            icono:         item.mascota.icono,
            foto:          item.mascota.foto || null,
            compatibilidad: item.compatibilidad
        };
    });
    var guardado = JSON.parse(localStorage.getItem('pf_test_compatibilidad_' + _pfUserId()) || '{}');
    guardado.ranking = rankingGuardar;
    localStorage.setItem('pf_test_compatibilidad_' + _pfUserId(), JSON.stringify(guardado));
}