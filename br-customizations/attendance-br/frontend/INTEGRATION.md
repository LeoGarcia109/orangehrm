# Integracao GPS no Frontend (RecordAttendance.vue)

## Arquivo a modificar

`src/client/src/orangehrmAttendancePlugin/components/RecordAttendance.vue`

## Passo 1: Copiar o composable

Copiar `useGeolocation.js` para:
```
src/client/src/orangehrmAttendancePlugin/composables/useGeolocation.js
```

## Passo 2: Importar no componente

Adicionar ao bloco `<script>`:

```javascript
import useGeolocation from '@/orangehrmAttendancePlugin/composables/useGeolocation';
```

## Passo 3: Modificar o metodo onSave

Substituir o metodo `onSave()` por:

```javascript
async onSave() {
  this.isLoading = true;

  const timezone = guessTimezone();

  // BR: Capturar geolocalizacao (nao-bloqueante)
  const {getCoordinates} = useGeolocation();
  const coords = await getCoordinates();

  this.http
    .request({
      method: this.attendanceRecordId ? 'PUT' : 'POST',
      data: {
        date: this.attendanceRecord.date,
        time: this.attendanceRecord.time,
        note: this.attendanceRecord.note,
        timezoneOffset:
          this.attendanceRecord.timezone?._offset ?? timezone.offset,
        timezoneName: this.attendanceRecord.timezone?.id ?? timezone.name,
        // BR: Coordenadas GPS (null se indisponivel)
        latitude: coords?.latitude ?? null,
        longitude: coords?.longitude ?? null,
      },
    })
    .then(() => {
      return this.$toast.saveSuccess();
    })
    .then(() => {
      this.employeeId
        ? navigate('/attendance/viewAttendanceRecord', undefined, {
            employeeId: this.employeeId,
            date: this.date,
          })
        : reloadPage();
    });
},
```

## Passo 4: Backend - aceitar os novos campos

No `EmployeeAttendanceRecordAPI.php`, adicionar ao `getCommonRequestParams()`:

```php
$this->getRequestParams()->getFloatOrNull(
    RequestParams::PARAM_TYPE_BODY,
    'latitude'
),
$this->getRequestParams()->getFloatOrNull(
    RequestParams::PARAM_TYPE_BODY,
    'longitude'
),
```

E no `setPunchInAttendanceRecord()` / `setPunchOutAttendanceRecord()`:

```php
if ($latitude !== null && $longitude !== null) {
    $attendanceRecord->setPunchInLatitude((string)$latitude);
    $attendanceRecord->setPunchInLongitude((string)$longitude);
}
```

## Notas

- A captura de GPS e **nao-bloqueante**: se o usuario negar permissao ou o
  dispositivo nao tiver GPS, o ponto e registrado normalmente sem coordenadas.
- O navegador exibe um prompt de permissao na primeira vez.
- Em HTTPS (obrigatorio para producao), a Geolocation API funciona nativamente.
- Em HTTP (dev local), alguns navegadores bloqueiam geolocation.
