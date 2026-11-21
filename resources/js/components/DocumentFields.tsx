import { FieldError } from './UI';

export default function DocumentFields({
  types,
  type,
  number,
  error,
  onType,
  onNumber,
}: {
  types: Record<string, string>;
  type: string;
  number: string;
  error?: string;
  onType: (value: string) => void;
  onNumber: (value: string) => void;
}) {
  return (
    <>
      <div className="field">
        <label htmlFor="document-type">Tipo de documento</label>
        <select id="document-type" value={type} onChange={(e) => onType(e.target.value)}>
          {Object.entries(types).map(([value, label]) => (
            <option key={value} value={value}>
              {label} ({value})
            </option>
          ))}
        </select>
      </div>
      <div className="field">
        <label htmlFor="document-number">Número de documento ficticio</label>
        <input
          id="document-number"
          value={number}
          onChange={(e) => onNumber(e.target.value)}
          required
          maxLength={30}
          autoComplete="username"
          placeholder="Ejemplo: LAB000001"
          aria-invalid={!!error}
          aria-describedby="document-error"
        />
        <FieldError id="document-error" message={error} />
      </div>
    </>
  );
}
