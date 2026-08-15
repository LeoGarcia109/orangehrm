/**
 * OrangeHRM BR - Geolocation composable for punch-in/out.
 *
 * Captures the employee's GPS coordinates when punching in or out.
 * Coordinates are sent along with the punch request and stored in
 * ohrm_attendance_record (punch_in_latitude/longitude, punch_out_latitude/longitude).
 *
 * Usage in RecordAttendance.vue:
 *
 *   import useGeolocation from './useGeolocation';
 *
 *   // In setup() or methods:
 *   const {getCoordinates} = useGeolocation();
 *
 *   // In onSave(), before the HTTP request:
 *   const coords = await getCoordinates();
 *   // Then include in request data:
 *   data: {
 *     ...existingFields,
 *     latitude: coords?.latitude ?? null,
 *     longitude: coords?.longitude ?? null,
 *   }
 */

export default function useGeolocation(options = {}) {
  const defaultOptions = {
    enableHighAccuracy: true,
    timeout: 10000,
    maximumAge: 60000, // Accept cached position up to 1 minute old
    ...options,
  };

  /**
   * Get the current GPS coordinates.
   * Returns null if geolocation is unavailable or denied (non-blocking).
   *
   * @returns {Promise<{latitude: number, longitude: number, accuracy: number}|null>}
   */
  function getCoordinates() {
    return new Promise((resolve) => {
      if (!navigator.geolocation) {
        resolve(null);
        return;
      }

      navigator.geolocation.getCurrentPosition(
        (position) => {
          resolve({
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy,
          });
        },
        (error) => {
          // Non-blocking: log and resolve null
          // eslint-disable-next-line no-console
          console.warn(
            '[BR Attendance] Geolocation unavailable:',
            error.message,
          );
          resolve(null);
        },
        defaultOptions,
      );
    });
  }

  /**
   * Check if geolocation is available and permitted.
   * @returns {Promise<boolean>}
   */
  async function isAvailable() {
    if (!navigator.geolocation) return false;
    try {
      const status = await navigator.permissions.query({name: 'geolocation'});
      return status.state !== 'denied';
    } catch {
      // Permissions API not supported, assume available
      return true;
    }
  }

  return {
    getCoordinates,
    isAvailable,
  };
}
